<?php

namespace Tests\Feature\Employee;

use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
