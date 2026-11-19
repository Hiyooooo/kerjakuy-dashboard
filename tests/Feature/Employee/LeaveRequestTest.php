<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Organization\Models\LeaveCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_submits_leave_across_years_and_each_day_uses_its_own_entitlement(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $category = LeaveCategory::factory()->create(['is_active' => true]);
        $common = ['employee_code' => $employee->employee_code, 'leave_category_id' => $category->id];
        $grant = route('admin.leave-requests.entitlements.store');
        $submit = route('admin.leave-requests.store');
        $data = [...$common, 'days' => [
            ['date' => '2026-12-31', 'duration' => '0.5'],
            ['date' => '2027-01-04', 'duration' => '1'],
        ]];

        $this->get(route('admin.leave-requests.create'))->assertOk()->assertSee('Hari cuti');
        $this->get(route('admin.leave-requests.entitlements.create'))->assertOk()->assertSee('Tahun jatah');
        $this->post($grant, [...$common, 'entitlement_year' => 2026, 'granted_days' => 12])->assertSessionHasNoErrors();
        $this->post($submit, $data)->assertSessionHasErrors('days.1.date');
        $this->assertDatabaseCount('requests', 0);
        $this->assertDatabaseCount('leave_request_days', 0);
        $this->post($grant, [...$common, 'entitlement_year' => 2027, 'granted_days' => 12])->assertSessionHasNoErrors();
        $this->post($grant, [...$common, 'entitlement_year' => 2027, 'granted_days' => 99])->assertSessionHasErrors('entitlement_year');
        $this->post($submit, [...$common, 'days' => [$data['days'][0], $data['days'][0]]])->assertSessionHasErrors('days.1.date');

        $this->post($submit, [...$data, 'attachment' => UploadedFile::fake()->image('cuti.png')])->assertSessionHasNoErrors();
        $request = EmployeeRequest::query()->sole();
        $this->assertSame('pending', $request->status);
        $this->assertSame('leave', $request->request_type);
        $this->assertSame($employee->id, $request->employee_id);
        $this->assertDatabaseCount('leave_request_details', 1);
        $entitlements = DB::table('employee_leave_entitlements')->pluck('id', 'entitlement_year');
        foreach ($data['days'] as $day) {
            $this->assertDatabaseHas('leave_request_days', [
                'leave_request_detail_id' => $request->id,
                'employee_leave_entitlement_id' => $entitlements[(int) substr($day['date'], 0, 4)],
                'leave_date' => $day['date'],
                'duration_days' => $day['duration'],
            ]);
        }
        $path = DB::table('leave_request_details')->where('request_id', $request->id)->value('attachment_path');
        Storage::disk('local')->assertExists($path);
        $this->get(route('admin.leave-requests.index'))->assertOk()->assertSee($request->request_number);
        $this->get(route('admin.leave-requests.show', $request))->assertOk()->assertSee('Jatah 2026')->assertSee('Jatah 2027');
        $this->get(route('admin.leave-requests.attachment', $request))->assertOk();

        $this->actingAs(User::factory()->create()->refresh());
        $this->get(route('admin.leave-requests.attachment', $request))->assertForbidden();
        $this->post($submit, $data)->assertForbidden();
    }
}
