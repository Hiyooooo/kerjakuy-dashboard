<?php

namespace Tests\Feature\Organization;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\AttendanceTime;
use App\Organization\Models\JobDesk;
use App\Organization\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_manages_job_desks_and_titles_are_unique_per_position(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $this->get(route('admin.job-desks.index'))->assertOk()->assertSee('Belum ada job desk');
        $this->get(route('admin.job-desks.create'))->assertOk()->assertSee(route('admin.positions.create'));

        $position = Position::create(['code' => 'HR', 'name' => 'Staf HR']);
        $otherPosition = Position::create(['code' => 'OPS', 'name' => 'Staf operasional']);
        $data = [
            'position_id' => $position->id,
            'title' => 'Administrasi',
            'job_description' => 'Kelola dokumen',
            'job_specification' => 'Pengalaman administrasi',
            'is_active' => '1',
        ];

        $this->get(route('admin.job-desks.create'))->assertOk()->assertSee('Staf HR')->assertSee('Spesifikasi jabatan');
        $this->post(route('admin.job-desks.store'), [...$data, 'position_id' => 99999])->assertSessionHasErrors('position_id');
        $this->post(route('admin.job-desks.store'), $data)->assertRedirectToRoute('admin.job-desks.index');
        $jobDesk = JobDesk::sole();
        $this->assertDatabaseHas('job_desks', $data);
        $this->post(route('admin.job-desks.store'), $data)->assertSessionHasErrors('title');
        $this->post(route('admin.job-desks.store'), [...$data, 'position_id' => $otherPosition->id])
            ->assertRedirectToRoute('admin.job-desks.index');

        $this->get(route('admin.job-desks.index'))->assertOk()->assertSee('Staf HR')->assertSee('Kelola dokumen');
        $this->get(route('admin.job-desks.edit', $jobDesk))->assertOk()->assertSee('Pengalaman administrasi');
        $this->put(route('admin.job-desks.update', $jobDesk), [...$data, 'job_specification' => 'Mahir spreadsheet', 'is_active' => '0'])
            ->assertRedirectToRoute('admin.job-desks.index');
        $jobDesk->refresh();
        $this->assertSame('Mahir spreadsheet', $jobDesk->job_specification);
        $this->assertFalse($jobDesk->is_active);

        $this->from(route('admin.positions.edit', $position))->delete(route('admin.positions.destroy', $position))
            ->assertSessionHasErrors('delete');
        $this->delete(route('admin.job-desks.destroy', $jobDesk))->assertRedirectToRoute('admin.job-desks.index');
        $this->assertModelMissing($jobDesk);
    }

    public function test_super_admin_manages_attendance_times_with_overnight_checkout(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'super_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $this->get(route('admin.attendance-times.index'))->assertOk()->assertSee('Belum ada waktu absen');
        $this->get(route('admin.attendance-times.create'))->assertOk()->assertSee('Toleransi terlambat');

        $data = AttendanceTime::factory()->raw([
            'name' => 'Jam malam',
            'check_in_start' => '21:30',
            'expected_check_in' => '22:00',
            'expected_check_out' => '06:00',
            'check_out_end' => '07:00',
        ]);
        $this->post(route('admin.attendance-times.store'), $data)->assertRedirectToRoute('admin.attendance-times.index');
        $time = AttendanceTime::sole();
        $this->post(route('admin.attendance-times.store'), $data)->assertSessionHasErrors('name');
        $this->get(route('admin.attendance-times.index'))->assertOk()->assertSee('22:00–06:00')->assertSee('hari berikutnya');
        $this->get(route('admin.attendance-times.edit', $time))->assertOk()->assertSee('value="07:00"', false);

        $this->put(route('admin.attendance-times.update', $time), [...$data, 'is_active' => '0', 'check_out_end' => null])
            ->assertRedirectToRoute('admin.attendance-times.index');
        $time->refresh();
        $this->assertNull($time->check_out_end);
        $this->assertFalse($time->is_active);
        $this->delete(route('admin.attendance-times.destroy', $time))->assertRedirectToRoute('admin.attendance-times.index');
        $this->assertModelMissing($time);
    }

    public function test_attendance_times_reject_invalid_windows_and_tolerance(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $data = AttendanceTime::factory()->raw();

        $this->post(route('admin.attendance-times.store'), [
            ...$data,
            'check_in_start' => '08:30',
            'expected_check_out' => '08:00',
            'late_tolerance_minutes' => '-1',
        ])->assertSessionHasErrors(['check_in_start', 'expected_check_out', 'late_tolerance_minutes']);

        $this->post(route('admin.attendance-times.store'), [...$data, 'check_out_end' => '16:45'])
            ->assertSessionHasErrors('check_out_end');
        $this->post(route('admin.attendance-times.store'), [
            ...$data,
            'check_in_start' => '21:30',
            'expected_check_in' => '22:00',
            'expected_check_out' => '06:00',
            'check_out_end' => '05:30',
        ])->assertSessionHasErrors('check_out_end');
        $this->assertDatabaseCount('attendance_times', 0);
    }

    public function test_guests_and_attendance_admins_cannot_manage_the_new_references(): void
    {
        $this->get(route('admin.job-desks.index'))->assertRedirectToRoute('login');
        $this->get(route('admin.attendance-times.index'))->assertRedirectToRoute('login');

        $this->actingAs(User::factory()->create()->refresh())->withoutVite();
        $jobDesk = JobDesk::factory()->create();
        $time = AttendanceTime::factory()->create();
        $this->get(route('admin.job-desks.edit', $jobDesk))->assertForbidden();
        $this->put(route('admin.attendance-times.update', $time))->assertForbidden();
        $this->delete(route('admin.job-desks.destroy', $jobDesk))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee(route('admin.job-desks.index'))
            ->assertDontSee(route('admin.attendance-times.index'));
    }
}
