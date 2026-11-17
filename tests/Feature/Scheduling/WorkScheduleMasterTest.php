<?php

namespace Tests\Feature\Scheduling;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\AttendanceTime;
use App\Organization\Services\AttendanceTimeService;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkScheduleMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_a_shift_and_save_all_seven_days_of_a_weekly_template(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $time = AttendanceTime::factory()->create(['name' => 'Absen malam', 'expected_check_out_day' => 1]);
        $this->get(route('admin.work-shifts.create'))->assertOk()->assertSee('Absen malam');
        $shiftData = ['attendance_time_id' => (string) $time->id, 'code' => 'MLM', 'name' => 'Shift malam', 'is_active' => '1'];
        $this->post(route('admin.work-shifts.store'), $shiftData)->assertRedirectToRoute('admin.work-shifts.index');
        $shift = WorkShift::sole();
        $this->assertSame($time->id, $shift->attendanceTime->id);
        $this->post(route('admin.work-shifts.store'), $shiftData)->assertSessionHasErrors('code');
        $this->get(route('admin.work-shifts.index'))->assertOk()->assertSee('pulang hari berikutnya');

        $store = route('admin.work-schedule-templates.store');
        $data = ['name' => 'Pola malam', 'is_active' => '1', 'days' => array_fill(1, 7, '0')];
        $data['days'][1] = (string) $shift->id;
        $this->get(route('admin.work-schedule-templates.create'))->assertOk()->assertSee('Minggu')->assertSee('Libur mingguan');
        $missing = $data;
        unset($missing['days'][7]);
        $this->post($store, $missing)->assertSessionHasErrors('days.7');
        $this->post($store, [...$data, 'days' => [1 => '99999'] + $data['days']])->assertSessionHasErrors('days.1');
        $this->assertDatabaseCount('work_schedule_templates', 0);

        $this->post($store, $data)->assertRedirectToRoute('admin.work-schedule-templates.index');
        $template = WorkScheduleTemplate::sole();
        $this->assertNull($template->office_id);
        $this->assertCount(7, $template->days);
        $this->assertTrue($template->days->first()->is_working_day);
        $this->assertNull($template->days->last()->work_shift_id);
        $this->get(route('admin.work-schedule-templates.index'))->assertOk()->assertSee('Senin · Shift malam')->assertSee('Minggu · Libur');

        $edit = route('admin.work-schedule-templates.edit', $template);
        $update = route('admin.work-schedule-templates.update', $template);
        $version = $template->editVersion();
        $this->get($edit)->assertOk()->assertSee($version);
        $this->put($update, [...$data, 'days' => [1 => '0', 7 => (string) $shift->id] + $data['days'], 'edit_version' => $version])
            ->assertRedirectToRoute('admin.work-schedule-templates.index');
        $this->put($update, [...$data, 'edit_version' => $version])->assertSessionHasErrors('edit_version');
        $this->assertSame($shift->id, $template->days()->where('weekday_number', 7)->sole()->work_shift_id);
    }

    public function test_active_templates_reject_inactive_references_and_overlaps_across_the_week_boundary(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $nightTime = AttendanceTime::factory()->create([
            'expected_check_in' => '22:00', 'expected_check_out' => '07:00', 'expected_check_out_day' => 1,
        ]);
        $night = WorkShift::factory()->create(['attendance_time_id' => $nightTime->id, 'is_active' => true]);
        $morningTime = AttendanceTime::factory()->create(['expected_check_in' => '06:00']);
        $morning = WorkShift::factory()->create(['attendance_time_id' => $morningTime->id, 'is_active' => false]);
        $data = [
            'name' => 'Pola akhir pekan',
            'is_active' => '1',
            'days' => [1 => $morning->id, 7 => $night->id] + array_fill(1, 7, '0'),
        ];
        $store = route('admin.work-schedule-templates.store');

        $this->post($store, $data)->assertSessionHasErrors(['days.1' => 'Pilih shift dan Waktu Absen aktif untuk Senin.']);
        $morning->update(['is_active' => true]);
        $this->post($store, $data)->assertSessionHasErrors(['days.1' => 'Shift Senin dimulai sebelum shift Minggu selesai.']);
        $morningTime->update(['expected_check_in' => '07:00']);
        $this->post($store, $data)->assertRedirectToRoute('admin.work-schedule-templates.index');

        $this->assertThrows(
            fn () => app(AttendanceTimeService::class)->update(['is_active' => false], $nightTime),
            fn (ValidationException $exception): bool => isset($exception->errors()['is_active']),
        );
        $this->assertTrue($nightTime->refresh()->is_active);

        $shiftData = [...$night->only($night->getFillable()), 'edit_version' => $night->editVersion()];
        $update = route('admin.work-shifts.update', $night);
        $this->put($update, [...$shiftData, 'is_active' => false])->assertSessionHasErrors('is_active');
        $this->put($update, [...$shiftData, 'name' => 'Nama terbaru'])->assertRedirectToRoute('admin.work-shifts.index');
        $this->put($update, $shiftData)->assertSessionHasErrors('edit_version');
        $this->assertSame('Nama terbaru', $night->refresh()->name);
    }

    public function test_failed_schedule_form_does_not_restore_input_in_another_tab(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $create = route('admin.work-shifts.create');
        $store = route('admin.work-shifts.store');
        $data = ['name' => 'Isian dari tab lain'];
        $this->post($store, $data)->assertSessionHasErrors('code');
        $this->get($create)->assertOk()->assertDontSee($data['name'])->assertDontSee('id="code-error"', false);
        $this->followingRedirects()->post($store, $data)->assertOk()
            ->assertSee($data['name'])->assertSee('id="code-error"', false);
    }

    public function test_attendance_admin_cannot_manage_scheduling_masters(): void
    {
        $this->actingAs(User::factory()->create()->refresh());
        $this->get(route('admin.work-shifts.index'))->assertForbidden();
        $this->post(route('admin.work-schedule-templates.store'))->assertForbidden();
    }
}
