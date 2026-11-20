<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Organization\Models\LeaveCategory;
use App\Scheduling\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_filters_and_records_only_one_decision_per_request(): void
    {
        $this->freezeSecond();
        $admin = $this->hrd();
        $this->actingAs($admin)->withoutVite();
        $late = EmployeeRequest::factory()->create(['request_type' => 'late']);
        $leave = EmployeeRequest::factory()->create(['request_type' => 'leave']);
        $index = route('admin.request-approvals.index', ['type' => 'late']);
        $this->get($index)->assertOk()->assertSee($late->request_number)->assertDontSee($leave->request_number);

        $decide = route('admin.request-approvals.decide', $late);
        $this->post($decide, ['decision' => 'approved'])->assertSessionHasErrors('approval');
        $this->post($decide, ['decision' => 'rejected', 'reviewer_note' => 'Perlu bukti tambahan'])
            ->assertRedirect(route('admin.request-approvals.index'))->assertSessionHasNoErrors();
        $this->assertSame('rejected', $late->refresh()->status);
        $this->assertSame($admin->id, $late->reviewed_by_user_id);
        $this->assertSame(now()->toDateTimeString(), $late->reviewed_at->toDateTimeString());
        $this->assertSame('Perlu bukti tambahan', $late->reviewer_note);
        $this->travel(1)->minute();
        $this->post($decide, ['decision' => 'cancelled'])->assertSessionHasErrors('approval');
        $this->assertSame('rejected', $late->refresh()->status);
        $this->assertSame(now()->subMinute()->toDateTimeString(), $late->reviewed_at->toDateTimeString());
        $this->assertSame('Perlu bukti tambahan', $late->reviewer_note);
        $this->get($index)->assertOk()->assertDontSee($late->request_number);
        $this->get(route('admin.request-approvals.index', ['status' => 'rejected']))
            ->assertOk()->assertSee($late->request_number)->assertSee('Perlu bukti tambahan');

        $cancel = route('admin.request-approvals.decide', $leave);
        $this->actingAs(User::factory()->create()->refresh());
        $this->get($index)->assertForbidden();
        $this->post($cancel, ['decision' => 'cancelled'])->assertForbidden();
        $this->actingAs($admin);
        $this->post($cancel, ['decision' => 'cancelled'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $leave->refresh()->status);
        $originalName = $admin->name;
        $admin->update(['name' => 'Nama peninjau baru']);
        $this->get(route('admin.request-approvals.index', ['status' => 'rejected']))->assertOk()->assertSee($originalName);
        $admin->delete();
        $this->assertNull($late->refresh()->reviewed_by_user_id);
        $this->assertSame($originalName, $late->reviewed_by_name);
        $this->actingAs($this->hrd())->get(route('admin.request-approvals.index', ['status' => 'rejected']))
            ->assertOk()->assertSee($originalName);
    }

    public function test_approval_rechecks_leave_balance_for_each_year(): void
    {
        $this->actingAs($this->hrd());
        $employee = Employee::factory()->create();
        $category = LeaveCategory::factory()->create();
        $common = ['employee_code' => $employee->employee_code, 'leave_category_id' => $category->id];
        foreach ([2027, 2028] as $year) {
            $this->post(route('admin.leave-requests.entitlements.store'), [
                ...$common, 'entitlement_year' => $year, 'granted_days' => 1,
            ])->assertSessionHasNoErrors();
        }

        $this->post(route('admin.leave-requests.store'), [
            ...$common, 'days' => [['date' => '2028-01-03', 'duration' => '0.75']],
        ])->assertSessionHasNoErrors();
        $first = EmployeeRequest::query()->sole();
        $this->post(route('admin.leave-requests.store'), [
            ...$common, 'days' => [['date' => '2027-12-31', 'duration' => '0.5'], ['date' => '2028-01-04', 'duration' => '0.5']],
        ])->assertSessionHasNoErrors();
        $second = EmployeeRequest::latest('id')->firstOrFail();

        $this->post(route('admin.request-approvals.decide', $first), ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->post(route('admin.request-approvals.decide', $second), ['decision' => 'approved'])
            ->assertSessionHasErrors(['approval' => 'Saldo cuti tahun 2028 tidak mencukupi.']);
        $this->assertSame('approved', $first->refresh()->status);
        $this->assertSame('pending', $second->refresh()->status);
        $this->assertNull($second->reviewed_at);
    }

    public function test_approval_limits_daily_leave_across_categories(): void
    {
        $this->actingAs($this->hrd());
        $employee = Employee::factory()->create();
        $categories = LeaveCategory::factory()->count(2)->create(['is_active' => true]);
        foreach ($categories as $category) {
            $this->post(route('admin.leave-requests.entitlements.store'), [
                'employee_code' => $employee->employee_code, 'leave_category_id' => $category->id,
                'entitlement_year' => 2027, 'granted_days' => 3,
            ])->assertSessionHasNoErrors();
        }

        foreach ([$categories[0], $categories[1], $categories[1]] as $index => $category) {
            $this->post(route('admin.leave-requests.store'), [
                'employee_code' => $employee->employee_code, 'leave_category_id' => $category->id,
                'days' => [['date' => '2027-01-05', 'duration' => '0.5']],
            ])->assertSessionHasNoErrors();
            $record = EmployeeRequest::latest('id')->firstOrFail();
            $response = $this->post(route('admin.request-approvals.decide', $record), ['decision' => 'approved']);
            if ($index < 2) {
                $response->assertSessionHasNoErrors();
                $this->assertSame('approved', $record->refresh()->status);
            } else {
                $response->assertSessionHasErrors('approval');
                $this->assertSame('pending', $record->refresh()->status);
            }
        }
    }

    public function test_late_approval_rechecks_attendance_recorded_after_submission(): void
    {
        $this->actingAs($this->hrd());
        $employee = Employee::factory()->create();
        $this->post(route('admin.late-requests.store'), [
            'employee_code' => $employee->employee_code, 'late_date' => '2027-01-05', 'reason' => 'Ada kendala perjalanan.',
        ])->assertSessionHasNoErrors();
        $record = EmployeeRequest::query()->sole();
        $attendance = AttendanceRecord::factory()->create([
            'employee_id' => $employee->id, 'work_date' => '2027-01-05', 'check_in_at' => '2027-01-05 01:00:00', 'late_minutes' => 0,
        ]);
        $decide = route('admin.request-approvals.decide', $record);
        $this->post($decide, ['decision' => 'approved'])->assertSessionHasErrors('approval');
        $this->assertSame('pending', $record->refresh()->status);
        $attendance->forceFill(['late_minutes' => 15])->saveOrFail();
        $this->post($decide, ['decision' => 'approved'])->assertSessionHasNoErrors();
        $this->assertSame('approved', $record->refresh()->status);
    }

    private function hrd(): User
    {
        return User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
    }
}
