<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Organization\Models\AttendanceTime;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ShiftRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_overtime_duration_supports_midnight_and_rejects_equal_times(): void
    {
        $this->actingAs($this->hrd())->withoutVite();
        $employee = Employee::factory()->create();
        $data = ['employee_code' => $employee->employee_code, 'overtime_date' => '2027-01-04',
            'start_time' => '23:30', 'end_time' => '01:00', 'reason' => 'Penutupan bulanan'];
        $store = route('admin.overtime-requests.store');
        $this->get(route('admin.overtime-requests.create'))->assertOk()->assertSee('Jam mulai (WIB)');
        $this->post($store, [...$data, 'end_time' => '23:30'])->assertSessionHasErrors('end_time');
        $this->post($store, $data)->assertSessionHasErrors('end_time');
        $this->post($store, [...$data, 'ends_next_day' => '1'])->assertSessionHasNoErrors();
        $this->post($store, [...$data, 'overtime_date' => '2027-01-05', 'start_time' => '00:30', 'end_time' => '02:00'])
            ->assertSessionHasErrors('start_time');
        $request = EmployeeRequest::query()->sole();
        $this->assertSame('overtime', $request->request_type);
        $this->assertSame('pending', $request->status);
        $this->assertDatabaseHas('overtime_request_details', ['request_id' => $request->id, 'total_minutes' => 90]);
        $this->get(route('admin.overtime-requests.show', $request))->assertOk()->assertSee('90 menit');
        $this->get(route('admin.shift-swap-requests.show', $request))->assertNotFound();
    }

    public function test_swap_approval_creates_both_overrides_and_records_the_decision_once(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2027, 1, 1));
        $admin = $this->hrd();
        $this->actingAs($admin)->withoutVite();
        $employee = Employee::factory()->create();
        $target = Employee::factory()->create();
        $first = WorkShift::factory()->create(['name' => 'Pagi']);
        $second = WorkShift::factory()->create(['name' => 'Siang']);
        $this->assign($employee, $first, [1]);
        $this->assign($target, $second, [2]);

        $request = $this->submitSwap($employee, $target);
        $this->post(route('admin.shift-swap-requests.store'), [
            'employee_code' => $target->employee_code, 'target_employee_code' => $employee->employee_code,
            'requester_work_date' => '2027-01-05', 'target_work_date' => '2027-01-04',
        ])->assertSessionHasErrors('target_work_date');
        $show = route('admin.shift-swap-requests.show', $request);
        $approve = route('admin.shift-swap-requests.approve', $request);
        $this->get($show)->assertOk()->assertSee('Pagi → Siang');
        $this->assertDatabaseCount('employee_shift_schedules', 0);
        $this->post($approve, ['reviewer_note' => 'Disepakati'])->assertRedirect($show)->assertSessionHasNoErrors();

        $this->assertSame($second->id, $employee->shiftOverrides()->sole()->work_shift_id);
        $this->assertSame($first->id, $target->shiftOverrides()->sole()->work_shift_id);
        $this->assertSame('2027-01-04', $employee->shiftOverrides()->sole()->work_date->toDateString());
        $this->assertSame('2027-01-05', $target->shiftOverrides()->sole()->work_date->toDateString());
        $this->assertSame('approved', $request->refresh()->status);
        $this->assertSame($admin->id, $request->reviewed_by_user_id);
        $this->assertSame('Disepakati', $request->reviewer_note);
        $this->post($approve)->assertSessionHasErrors('approval');
        $this->assertDatabaseCount('employee_shift_schedules', 2);
    }

    public function test_conflict_on_second_override_rolls_back_the_first_and_keeps_the_request_pending(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2027, 1, 1));
        $this->actingAs($this->hrd());
        $employee = Employee::factory()->create();
        $target = Employee::factory()->create();
        $time = AttendanceTime::factory()->create([
            'check_in_start' => '19:30', 'expected_check_in' => '20:00',
            'expected_check_out' => '09:00', 'expected_check_out_day' => 1,
            'check_out_end' => '10:00', 'check_out_end_day' => 1,
        ]);
        $night = WorkShift::factory()->for($time, 'attendanceTime')->create();
        $day = WorkShift::factory()->create();
        $this->assign($employee, $night, [1]);
        $this->assign($target, $day, [2, 3]);
        $request = $this->submitSwap($employee, $target);

        $this->post(route('admin.shift-swap-requests.approve', $request))->assertSessionHasErrors('work_date');
        $this->assertDatabaseCount('employee_shift_schedules', 0);
        $this->assertSame('pending', $request->refresh()->status);
        $this->assertNull($request->reviewed_by_user_id);
    }

    public function test_swap_rechecks_edited_hours_and_previous_day_check_in_window(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2027, 1, 1)->startOfDay());
        $this->actingAs($this->hrd());
        $employee = Employee::factory()->create();
        $target = Employee::factory()->create();
        $time = AttendanceTime::factory()->create(['check_in_start' => '22:00', 'check_in_start_day' => -1]);
        $this->assign($employee, WorkShift::factory()->for($time, 'attendanceTime')->create(), [1]);
        $this->assign($target, WorkShift::factory()->create(), [2]);
        $request = $this->submitSwap($employee, $target);
        $approve = route('admin.shift-swap-requests.approve', $request);

        $time->update(['expected_check_in' => '08:15']);
        $this->post($approve)->assertSessionHasErrors(['approval' => 'Shift efektif sudah berubah sejak pengajuan. Buat permohonan baru.']);
        $time->update(['expected_check_in' => '08:00']);
        $this->travelTo(now('Asia/Jakarta')->setDate(2027, 1, 3)->setTime(22, 1));
        $this->post($approve)->assertSessionHasErrors(['approval' => 'Pertukaran harus diproses sebelum jendela absen masuk dibuka.']);
        $this->assertDatabaseCount('employee_shift_schedules', 0);
        $this->assertSame('pending', $request->refresh()->status);
    }

    private function hrd(): User
    {
        return User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
    }

    private function assign(Employee $employee, WorkShift $shift, array $weekdays): void
    {
        $template = WorkScheduleTemplate::factory()->create(['is_active' => true]);
        $template->days()->whereIn('weekday_number', $weekdays)->update(['work_shift_id' => $shift->id, 'is_working_day' => true]);
        DB::table('employee_work_schedule_assignments')->insert([
            'employee_id' => $employee->id,
            'work_schedule_template_id' => $template->id,
            'effective_start_date' => '2027-01-01',
            'assigned_by_user_id' => auth()->id(),
        ]);
    }

    private function submitSwap(Employee $employee, Employee $target): EmployeeRequest
    {
        $this->post(route('admin.shift-swap-requests.store'), [
            'employee_code' => $employee->employee_code, 'target_employee_code' => $target->employee_code,
            'requester_work_date' => '2027-01-04', 'target_work_date' => '2027-01-05',
        ])->assertSessionHasNoErrors();

        return EmployeeRequest::where('request_type', 'shift_swap')->sole();
    }
}
