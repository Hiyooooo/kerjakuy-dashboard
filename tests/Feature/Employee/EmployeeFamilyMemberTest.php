<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeFamilyMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_hrd_can_record_and_end_a_tax_dependency(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2027, 6, 1));
        $this->actingAs($this->hrd())->withoutVite();
        $employee = Employee::factory()->create();
        $index = route('admin.employees.family-members.index', $employee);
        $this->get($index)->assertOk()->assertSee('Belum ada anggota keluarga');
        $this->get(route('admin.employees.family-members.create', $employee))->assertOk()->assertSee('Tanggal mulai');

        $data = [
            'full_name' => 'Rina Contoh', 'relationship' => 'Anak', 'birth_date' => '2017-02-08',
            'occupation' => 'Pelajar', 'phone' => '081234567890',
            'is_tax_dependent' => '1', 'tax_dependent_start_date' => '2027-01-01',
        ];
        $this->post(route('admin.employees.family-members.store', $employee), $data)
            ->assertRedirect($index)->assertSessionHasNoErrors();
        $member = $employee->familyMembers()->sole();
        $this->assertTrue($member->is_tax_dependent);
        $this->assertSame('081234567890', $member->phone);
        $this->get($index)->assertOk()->assertSee('Rina Contoh')->assertSee('Tanggungan aktif');

        $update = route('admin.employees.family-members.update', [$employee, $member]);
        $edit = route('admin.employees.family-members.edit', [$employee, $member]);
        $version = $member->editVersion();
        $this->get($edit)->assertOk()->assertSee($data['phone']);
        $this->put($update, [...$data, 'edit_version' => $version, 'tax_dependent_end_date' => '2027-05-31'])
            ->assertRedirect($index)->assertSessionHasNoErrors();
        $this->get($index)->assertOk()->assertSee('Tanggungan berakhir');
        $this->put($update, [...$data, 'edit_version' => $version])
            ->assertRedirect($edit)->assertSessionHasErrors('edit_version', null, $edit);
        $this->assertSame('2027-05-31', $member->refresh()->tax_dependent_end_date->toDateString());

        $renewal = [...$data, 'edit_version' => $member->editVersion(), 'tax_dependent_start_date' => '2027-07-01'];
        $this->put($update, $renewal)->assertSessionHasErrorsIn($edit, 'start_new_tax_period');
        $this->put($update, [...$renewal, 'start_new_tax_period' => '1'])
            ->assertRedirect($index)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employee_family_tax_periods', [
            'employee_family_member_id' => $member->id, 'start_date' => '2027-01-01', 'end_date' => '2027-05-31',
        ]);
        $this->assertSame('2027-07-01', $member->refresh()->tax_dependent_start_date->toDateString());
        $this->get($edit)->assertOk()->assertSee('Riwayat periode tanggungan')->assertSee('31 Mei 2027');

        $this->put($update, [...$data, 'edit_version' => $member->editVersion()])
            ->assertSessionHasErrorsIn($edit, 'tax_dependent_start_date');
    }

    public function test_edit_compares_dependency_start_with_the_stored_birth_date_if_omitted(): void
    {
        $this->actingAs($this->hrd());
        $employee = Employee::factory()->create();
        $member = $employee->familyMembers()->create([
            'full_name' => 'Rina Contoh', 'relationship' => 'Anak', 'birth_date' => '2017-02-08',
        ])->refresh();

        $this->putJson(route('admin.employees.family-members.update', [$employee, $member]), [
            'full_name' => $member->full_name, 'relationship' => $member->relationship, 'is_tax_dependent' => true,
            'tax_dependent_start_date' => '2017-02-07', 'edit_version' => $member->editVersion(),
        ])->assertUnprocessable()->assertJsonValidationErrors('tax_dependent_start_date');

        $this->assertNull($member->refresh()->tax_dependent_start_date);
    }

    public function test_family_period_validation_and_employee_scope(): void
    {
        $this->actingAs($this->hrd())->withoutVite();
        $employee = Employee::factory()->create();
        $otherEmployee = Employee::factory()->create();
        $member = $otherEmployee->familyMembers()->create(['full_name' => 'Nama Contoh', 'relationship' => 'Anak']);

        $create = route('admin.employees.family-members.create', $employee);
        $store = route('admin.employees.family-members.store', $employee);
        $data = ['full_name' => 'Rina Contoh', 'relationship' => 'Anak', 'is_tax_dependent' => '1'];

        $this->post($store, $data)->assertSessionHasErrors('tax_dependent_start_date', null, $create);
        $this->post($store, [...$data, 'tax_dependent_start_date' => '2027-02-01', 'tax_dependent_end_date' => '2027-01-31'])
            ->assertSessionHasErrors('tax_dependent_end_date', null, $create);
        $this->post($store, [...$data, 'is_tax_dependent' => '0', 'tax_dependent_start_date' => '2027-02-01'])
            ->assertSessionHasErrors('tax_dependent_start_date', null, $create);
        $this->assertSame(0, $employee->familyMembers()->count());

        $this->get(route('admin.employees.family-members.edit', [$employee, $member]))->assertNotFound();
        $this->put(route('admin.employees.family-members.update', [$employee, $member]), $data)->assertNotFound();
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.employees.family-members.index', $employee))->assertForbidden();
        $this->post($store, $data)->assertForbidden();
    }

    private function hrd(): User
    {
        return User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
    }
}
