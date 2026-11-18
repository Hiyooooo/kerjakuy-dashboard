<?php

namespace Tests\Feature\Scheduling;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Organization\Models\AttendanceTime;
use App\Organization\Models\Office;
use App\Organization\Models\Position;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_schedule_is_resolved_by_date_and_a_cancelled_override_restores_it(): void
    {
        $admin = User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $shift = WorkShift::factory()->create(['name' => 'Shift pagi']);
        $special = WorkShift::factory()->create(['name' => 'Shift khusus sore']);
        $template = WorkScheduleTemplate::factory()->create(['is_active' => true]);
        $template->days()->where('weekday_number', 1)->update(['work_shift_id' => $shift->id, 'is_working_day' => true]);

        $index = route('admin.employees.schedules.index', $employee);
        $store = route('admin.employees.schedule-assignments.store', $employee);
        $this->get(route('admin.employees.schedule-assignments.create', $employee))->assertOk()->assertSee('Template jadwal');
        $data = [
            'work_schedule_template_id' => $template->id,
            'effective_start_date' => '2027-01-04',
            'effective_end_date' => '2027-01-11',
        ];
        $this->post($store, $data)->assertRedirect($index);
        $assignment = $employee->scheduleAssignments()->sole();
        $this->assertSame($admin->id, $assignment->assigned_by_user_id);
        $this->post($store, [...$data, 'effective_start_date' => '2027-01-11'])->assertSessionHasErrors('effective_start_date');
        $this->get(route('admin.employees.schedule-assignments.edit', [$employee, $assignment]))->assertOk();
        $this->get(route('admin.employees.schedule-assignments.edit', [$other, $assignment]))->assertNotFound();
        $this->get($index.'?date=2027-01-11')->assertOk()->assertSee('Shift pagi');
        $this->get($index.'?date=2027-01-05')->assertOk()->assertSee('Libur mingguan');
        $this->get($index.'?date=2027-01-12')->assertOk()->assertSee('Belum ada jadwal default pada tanggal ini');

        $overrideStore = route('admin.employees.shift-overrides.store', $employee);
        $override = ['work_shift_id' => $special->id, 'work_date' => '2027-01-11', 'status' => 'scheduled'];
        $this->get(route('admin.employees.shift-overrides.create', [$employee, 'date' => $override['work_date']]))->assertOk()
            ->assertSee('value="2027-01-11"', false);
        $this->post($overrideStore, $override)->assertRedirect($index.'?date=2027-01-11');
        $this->get($index.'?date=2027-01-11')->assertOk()->assertSee('Shift khusus sore')->assertDontSee('Shift pagi');
        $record = $employee->shiftOverrides()->sole();
        $this->assertSame($admin->id, $record->created_by_user_id);
        $this->post($overrideStore, $override)->assertSessionHasErrors('work_date');
        $this->get(route('admin.employees.shift-overrides.edit', [$other, $record]))->assertNotFound();
        $this->put(route('admin.employees.shift-overrides.update', [$employee, $record]), [
            ...$override, 'status' => 'cancelled', 'edit_version' => $record->editVersion(),
        ])->assertRedirect($index.'?date=2027-01-11');
        $this->get($index.'?date=2027-01-11')->assertOk()->assertSee('Shift pagi')->assertSee('Dibatalkan');
    }

    public function test_default_schedule_periods_cannot_overlap_for_the_same_employee(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $template = WorkScheduleTemplate::factory()->create(['is_active' => true]);
        $store = route('admin.employees.schedule-assignments.store', $employee);
        $data = [
            'work_schedule_template_id' => $template->id,
            'effective_start_date' => '2027-01-01',
            'effective_end_date' => '2027-01-10',
        ];

        $this->post($store, $data)->assertSessionHasNoErrors();
        $first = $employee->scheduleAssignments()->sole();

        $this->post($store, [...$data, 'effective_start_date' => '2027-01-11', 'effective_end_date' => null])
            ->assertSessionHasNoErrors();
        $this->post($store, [...$data, 'effective_start_date' => '2027-02-01', 'effective_end_date' => null])
            ->assertSessionHasErrors(['effective_start_date' => 'Periode bertumpang tindih dengan jadwal default mulai 11 Januari 2027 tanpa tanggal akhir. Sesuaikan tanggal mulai atau tanggal akhir.']);

        $update = route('admin.employees.schedule-assignments.update', [$employee, $first]);
        $this->put($update, [...$data, 'edit_version' => $first->editVersion()])->assertSessionHasNoErrors();
        $this->put($update, [...$data, 'effective_end_date' => '2027-01-11', 'edit_version' => $first->refresh()->editVersion()])
            ->assertSessionHasErrors('effective_start_date');
        $this->assertSame('2027-01-10', $first->refresh()->effective_end_date->toDateString());

        $this->post(route('admin.employees.schedule-assignments.store', $other), $data)->assertSessionHasNoErrors();
        $this->assertCount(2, $employee->scheduleAssignments);
        $this->assertCount(1, $other->scheduleAssignments);
    }

    public function test_attendance_admin_cannot_manage_employee_schedules(): void
    {
        $this->actingAs(User::factory()->create()->refresh());
        $employee = Employee::factory()->create();
        $this->get(route('admin.employees.schedules.index', $employee))->assertForbidden();
        $this->post(route('admin.employees.shift-overrides.store', $employee))->assertForbidden();
    }

    public function test_overnight_override_cannot_overlap_the_next_days_default_shift(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $employee = Employee::factory()->create();
        $day = WorkShift::factory()->create();
        $night = WorkShift::factory()->for(AttendanceTime::factory()->state([
            'check_in_start' => '20:00',
            'expected_check_in' => '21:00',
            'expected_check_out' => '09:00',
            'expected_check_out_day' => 1,
            'check_out_end' => '10:00',
            'check_out_end_day' => 1,
        ]), 'attendanceTime')->create();
        $template = WorkScheduleTemplate::factory()->create(['is_active' => true]);
        $template->days()->where('weekday_number', 1)->update(['work_shift_id' => $day->id, 'is_working_day' => true]);
        $this->post(route('admin.employees.schedule-assignments.store', $employee), [
            'work_schedule_template_id' => $template->id,
            'effective_start_date' => '2027-01-04',
        ])->assertSessionHasNoErrors();

        $this->post(route('admin.employees.shift-overrides.store', $employee), [
            'work_shift_id' => $night->id,
            'work_date' => '2027-01-03',
            'status' => 'scheduled',
        ])->assertSessionHasErrors('work_date');
        $this->assertFalse($employee->shiftOverrides()->exists());

        $this->post(route('admin.employees.shift-overrides.store', $employee), [
            'work_shift_id' => $night->id,
            'work_date' => '2027-01-04',
            'status' => 'scheduled',
        ])->assertSessionHasNoErrors();
        $replacement = $employee->shiftOverrides()->sole();
        $this->post(route('admin.employees.shift-overrides.store', $employee), [
            'work_shift_id' => $night->id,
            'work_date' => '2027-01-03',
            'status' => 'scheduled',
        ])->assertSessionHasNoErrors();

        $this->put(route('admin.employees.shift-overrides.update', [$employee, $replacement]), [
            'work_shift_id' => $night->id,
            'work_date' => '2027-01-04',
            'status' => 'cancelled',
            'edit_version' => $replacement->editVersion(),
        ])->assertSessionHasErrors('work_date');
        $this->assertSame('scheduled', $replacement->refresh()->status);
    }

    public function test_office_template_requires_full_placement_coverage_and_reports_deactivation(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2027, 1, 1));
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $employee = Employee::factory()->create();
        $office = Office::create(['name' => 'Kantor Utama', 'code' => 'UTAMA']);
        $employee->assignments()->create([
            'office_id' => $office->id,
            'position_id' => Position::create(['name' => 'Staf', 'code' => 'STAF'])->id,
            'change_type' => 'Penempatan awal',
            'effective_start_date' => '2027-01-01',
            'effective_end_date' => '2027-01-05',
        ]);
        $template = WorkScheduleTemplate::factory()->create(['office_id' => $office->id, 'is_active' => true]);
        $store = route('admin.employees.schedule-assignments.store', $employee);
        $data = [
            'work_schedule_template_id' => $template->id,
            'effective_start_date' => '2027-01-01',
        ];
        $this->post($store, $data)->assertSessionHasErrors('work_schedule_template_id');
        $this->post($store, [...$data, 'effective_end_date' => '2027-01-05'])->assertSessionHasNoErrors();

        $template->update(['is_active' => false]);
        $this->get(route('admin.employees.schedules.index', [$employee, 'date' => '2027-01-04']))
            ->assertOk()->assertSee('Template jadwal default sudah nonaktif');
    }
}
