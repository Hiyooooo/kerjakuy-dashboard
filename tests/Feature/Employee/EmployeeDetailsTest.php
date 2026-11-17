<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_nik_is_encrypted_unique_and_never_returned_to_the_form(): void
    {
        $first = Employee::factory()->create();
        $second = Employee::factory()->create();
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $nik = '0000000000000001';
        $edit = route('admin.employees.identity.edit', $first);
        $update = route('admin.employees.identity.update', $first);
        $data = ['gender' => 'female', 'national_id' => $nik, 'edit_version' => 'new'];

        $this->put($update, ['gender' => 'female', 'edit_version' => 'new'])->assertRedirect($edit)->assertSessionHasErrors('national_id', null, $edit);
        $this->put($update, $data)->assertRedirect($edit);
        $identity = $first->identityDetail()->sole();
        $raw = DB::table('employee_identity_details')->first();
        $this->assertSame($nik, $identity->national_id_ciphertext);
        $this->assertNotSame($nik, $raw->national_id_ciphertext);
        $this->assertNotSame(hash('sha256', $nik), $raw->national_id_hash);
        $this->assertArrayNotHasKey('national_id_ciphertext', $identity->toArray());
        $this->get($edit)->assertOk()->assertSee('NIK sudah tersimpan')->assertDontSee($nik);
        $version = $identity->editVersion();
        $this->put($update, ['gender' => 'male', 'national_id' => '', 'edit_version' => $version])->assertRedirect($edit);
        $this->assertSame($raw->national_id_ciphertext, $identity->refresh()->getRawOriginal('national_id_ciphertext'));

        $this->put($update, [...$data, 'edit_version' => $version, 'national_id' => '0000000000000002'])
            ->assertRedirect($edit)->assertSessionHasErrors('edit_version', null, $edit)
            ->assertSessionMissing('_old_input.national_id');
        $this->assertSame('male', $identity->refresh()->gender);
        $this->assertSame($nik, $identity->national_id_ciphertext);

        $secondEdit = route('admin.employees.identity.edit', $second);
        $this->put(route('admin.employees.identity.update', $second), $data)
            ->assertRedirect($secondEdit)->assertSessionHasErrors('national_id', null, $secondEdit)
            ->assertSessionMissing('_old_input.national_id');
        $this->assertDatabaseCount('employee_identity_details', 1);
    }

    public function test_employment_periods_reject_overlap_and_are_scoped_to_the_employee(): void
    {
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $index = route('admin.employees.periods.index', $employee);
        $store = route('admin.employees.periods.store', $employee);
        $create = route('admin.employees.periods.create', $employee);
        $data = ['employment_type' => 'contract', 'status' => 'active', 'effective_start_date' => '2026-01-01'];

        $this->get($create)->assertOk()->assertSee('Jenis hubungan kerja');
        $this->post($store, $data)->assertRedirect($index);
        $period = $employee->periods()->sole();
        $edit = route('admin.employees.periods.edit', [$employee, $period]);
        $this->get($edit)->assertOk()->assertSee($period->editVersion());
        $this->get(route('admin.employees.periods.edit', [$other, $period]))->assertNotFound();

        $next = ['employment_type' => 'permanent', 'status' => 'inactive', 'effective_start_date' => '2026-07-01'];
        $this->post($store, $next)->assertSessionHasErrors('effective_start_date', null, $create);

        $this->put(route('admin.employees.periods.update', [$employee, $period]), [
            ...$data, 'effective_end_date' => '2026-06-30', 'edit_version' => $period->editVersion(),
        ])->assertRedirect($index);
        $this->post($store, [...$next, 'effective_start_date' => '2026-06-30'])
            ->assertSessionHasErrors('effective_start_date', null, $create);
        $this->post($store, $next)->assertRedirect($index);
        $this->get($index)->assertOk()->assertSee('Kontrak')->assertSee('Tetap')->assertSee('Nonaktif');
    }

    public function test_attendance_admin_cannot_access_employee_details(): void
    {
        $employee = Employee::factory()->create();
        $this->actingAs(User::factory()->create()->refresh());
        $this->get(route('admin.employees.identity.edit', $employee))->assertForbidden();
        $this->post(route('admin.employees.periods.store', $employee))->assertForbidden();
    }
}
