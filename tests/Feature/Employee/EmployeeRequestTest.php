<?php

namespace Tests\Feature\Employee;

use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Employee\Services\ShiftRequestService;
use Database\Seeders\EmployeeRequestSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_four_request_types_share_the_same_header(): void
    {
        $employee = Employee::factory()->create();
        foreach (['leave', 'late', 'overtime', 'shift_swap'] as $type) {
            $request = EmployeeRequest::factory()->for($employee)->create(['request_type' => $type])->refresh();
            $this->assertSame('pending', $request->status);
            $this->assertSame($type, $request->request_type);
            $this->assertTrue($request->employee->is($employee));
            $this->assertNull($request->reviewer);
            $this->assertNull($request->reviewed_at);
        }

        $reviewer = User::factory()->create();
        $request->reviewer()->associate($reviewer);
        $request->saveOrFail();
        $this->assertTrue($request->refresh()->reviewer->is($reviewer));
        $this->assertSame(4, $employee->requests()->count());
        $this->assertDatabaseCount('requests', 4);
    }

    public function test_request_numbers_are_unique_across_types(): void
    {
        $request = EmployeeRequest::factory()->create();

        $this->expectException(UniqueConstraintViolationException::class);
        EmployeeRequest::factory()->create([
            'request_number' => $request->request_number,
            'request_type' => 'shift_swap',
        ]);
    }

    public function test_postgresql_requires_exactly_one_detail_matching_the_request_type(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires PostgreSQL deferred constraints.');
        }

        $this->seed(EmployeeRequestSeeder::class);
        DB::statement('SET CONSTRAINTS employee_request_detail_type IMMEDIATE');
        DB::statement('SET CONSTRAINTS employee_request_detail_type DEFERRED');
        $leave = EmployeeRequest::where('request_type', 'leave')->sole();
        $lateDetail = ['late_date' => '2027-01-05', 'reason' => 'Kendala perjalanan'];

        $invalidMutations = [
            fn () => EmployeeRequest::factory()->create(),
            fn () => $leave->update(['request_type' => 'late']),
            fn () => DB::table('late_request_details')->insert(['request_id' => $leave->id, ...$lateDetail]),
            fn () => DB::table('leave_request_details')->where('request_id', $leave->id)->delete(),
        ];

        foreach ($invalidMutations as $mutate) {
            try {
                DB::transaction(function () use ($mutate): void {
                    $mutate();
                    DB::statement('SET CONSTRAINTS employee_request_detail_type IMMEDIATE');
                });
                $this->fail('PostgreSQL accepted an invalid request detail.');
            } catch (QueryException $exception) {
                $this->assertSame('23514', $exception->getCode());
                $this->assertStringContainsString('must have exactly one', $exception->getMessage());
            }
        }

        $leave->refresh()->delete();
        DB::statement('SET CONSTRAINTS employee_request_detail_type IMMEDIATE');
        $this->assertDatabaseMissing('leave_request_details', ['request_id' => $leave->id]);
    }

    public function test_seeded_shift_swap_can_be_approved(): void
    {
        $this->seed(EmployeeRequestSeeder::class);
        $request = EmployeeRequest::where('request_type', 'shift_swap')->sole();
        $detail = DB::table('shift_swap_request_details')->where('request_id', $request->id)->sole();

        app(ShiftRequestService::class)->approveSwap($request, User::factory()->create(), null);

        $this->assertSame('approved', $request->refresh()->status);
        $this->assertDatabaseHas('employee_shift_schedules', [
            'employee_id' => $request->employee_id, 'work_shift_id' => $detail->target_work_shift_id,
        ]);
        $this->assertDatabaseHas('employee_shift_schedules', [
            'employee_id' => $detail->target_employee_id, 'work_shift_id' => $detail->requester_work_shift_id,
        ]);
    }

    public function test_postgresql_backfills_legacy_approver_names_and_requires_decision_metadata(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('Requires PostgreSQL.');
        }

        $this->seed(EmployeeRequestSeeder::class);
        DB::statement('SET CONSTRAINTS employee_request_detail_type IMMEDIATE');
        $migration = require database_path('migrations/2026_11_20_032807_require_decision_metadata_on_requests_table.php');
        $migration->down();
        $legacy = EmployeeRequest::where('request_type', 'leave')->sole();
        $reviewer = User::factory()->create();
        $legacy->forceFill([
            'status' => 'approved', 'reviewed_by_user_id' => $reviewer->id, 'reviewed_at' => now(),
        ])->saveOrFail();

        $migration->up();
        $this->assertSame($reviewer->name, $legacy->refresh()->reviewed_by_name);
        $reviewer->delete();
        $this->assertNull($legacy->refresh()->reviewed_by_user_id);
        $this->assertNotNull($legacy->reviewed_by_name);

        $pending = EmployeeRequest::where('request_type', 'late')->sole();
        foreach ([
            ['reviewed_at' => now(), 'reviewed_by_name' => null],
            ['reviewed_at' => null, 'reviewed_by_name' => $reviewer->name],
        ] as $metadata) {
            try {
                DB::transaction(fn () => DB::table('requests')->where('id', $pending->id)
                    ->update(['status' => 'rejected', ...$metadata]));
                $this->fail('PostgreSQL accepted an incomplete decision.');
            } catch (QueryException $exception) {
                $this->assertSame('23514', $exception->getCode());
                $this->assertStringContainsString('requests_decision_metadata_check', $exception->getMessage());
            }
        }
    }
}
