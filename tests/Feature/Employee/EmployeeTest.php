<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Organization\Models\Office;
use App\Organization\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_store_and_update_only_core_employee_data(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $index = route('admin.employees.index');
        $store = route('admin.employees.store');
        $this->get($index)->assertOk()->assertSee('Belum ada pegawai');
        $this->get(route('admin.employees.create'))->assertOk()
            ->assertSee('Kode pegawai')
            ->assertSee('Isi data dasar untuk membuat profil.')
            ->assertSee('Buat profil pegawai');

        $data = [
            'employee_code' => 'pgw-001',
            'full_name' => 'Ayu Pratama',
            'email' => 'AYU@example.test',
            'phone' => '081234567890',
            'birth_date' => '1995-08-17',
            'address' => 'Alamat privat',
        ];
        $created = $this->post($store, [...$data, 'position_id' => 1, 'office_id' => 1, 'is_active' => false]);
        $employee = Employee::sole();
        $edit = route('admin.employees.edit', $employee);
        $created->assertRedirect($edit);
        $this->get($edit)->assertOk()
            ->assertSee('Profil pegawai berhasil dibuat.')
            ->assertSee('Lengkapi identitas');
        $this->assertSame('PGW-001', $employee->employee_code);
        $this->assertSame('ayu@example.test', $employee->email);
        $this->assertSame('1995-08-17', $employee->birth_date->toDateString());
        $this->assertEqualsCanonicalizing(
            ['id', 'employee_code', 'full_name', 'email', 'phone', 'birth_date', 'address', 'created_at', 'updated_at'],
            Schema::getColumnListing('employees'),
        );
        $this->get($index)->assertOk()->assertSee('Ayu Pratama')->assertSee('PGW-001')->assertDontSee('Alamat privat');
        $update = route('admin.employees.update', $employee);
        $version = $employee->editVersion();
        $this->get($edit)->assertOk()->assertSee($version)->assertSee('Alamat privat');
        $this->put($update, [...$data, 'phone' => '', 'edit_version' => $version])->assertRedirect($index);
        $this->followingRedirects()->from($edit)->put($update, [...$data, 'edit_version' => $version])
            ->assertOk()->assertSee('Buka versi terbaru');
        $this->assertNull($employee->refresh()->phone);
        $this->post($store, [...$data, 'employee_code' => 'PGW-001', 'email' => 'ayu@example.test'])
            ->assertSessionHasErrors(['employee_code', 'email'], null, 'employee_create');
    }

    public function test_failed_edit_only_restores_input_on_its_own_form(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $first = Employee::factory()->create();
        $second = Employee::factory()->create(['full_name' => 'Pegawai Kedua']);
        $update = route('admin.employees.update', $first);
        $edit = route('admin.employees.edit', $first);
        $data = ['full_name' => 'Isian privat dari tab pertama', 'edit_version' => $first->editVersion()];

        $this->put($update, $data)->assertRedirect($edit);
        $this->get(route('admin.employees.edit', $second))->assertOk()
            ->assertSee('Pegawai Kedua')->assertSee($second->editVersion())
            ->assertDontSee($data['full_name'])->assertDontSee('id="employee_code-error"', false);

        $this->followingRedirects()->put($update, $data)->assertOk()
            ->assertSee($data['full_name'])->assertSee('id="employee_code-error"', false);
    }

    public function test_employee_search_matches_name_or_code_and_treats_wildcards_as_text(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        Employee::factory()->create(['full_name' => 'Ayu Pratama', 'employee_code' => 'HR_001']);
        Employee::factory()->create(['full_name' => 'Budi Santoso', 'employee_code' => 'HRX001']);

        $this->get(route('admin.employees.index', ['search' => 'pRAT']))
            ->assertOk()->assertSee('Ayu Pratama')->assertDontSee('Budi Santoso');
        $this->get(route('admin.employees.index', ['search' => 'hr_']))
            ->assertOk()->assertSee('Ayu Pratama')->assertDontSee('Budi Santoso');
        $this->get(route('admin.employees.index', ['search' => '%']))
            ->assertOk()->assertSee('Tidak ada pegawai yang cocok');
    }

    public function test_inactive_page_only_lists_employees_with_an_effective_inactive_or_resigned_period(): void
    {
        $this->travelTo(today('Asia/Jakarta')->setDate(2027, 1, 10));
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();

        foreach ([
            ['Ayu Nonaktif', 'inactive', '2027-01-10', '2027-01-10'],
            ['Budi Resign', 'resigned', '2027-01-01', null],
            ['Citra Aktif', 'active', '2027-01-01', null],
            ['Doni Mendatang', 'inactive', '2027-01-11', null],
            ['Eka Berakhir', 'inactive', '2027-01-01', '2027-01-09'],
        ] as [$name, $status, $start, $end]) {
            Employee::factory()->create(['full_name' => $name])->periods()->create([
                'employment_type' => 'contract',
                'status' => $status,
                'effective_start_date' => $start,
                'effective_end_date' => $end,
            ]);
        }
        Employee::factory()->create(['full_name' => 'Fani Tanpa Periode']);
        $employee = Employee::where('full_name', 'Ayu Nonaktif')->sole();
        $office = Office::create(['name' => 'Kantor Hari Ini', 'code' => 'NOW']);
        $position = Position::create(['name' => 'Jabatan Hari Ini', 'code' => 'NOW']);
        $assignment = [
            'office_id' => $office->id,
            'position_id' => $position->id,
            'change_type' => 'Penempatan awal',
            'effective_start_date' => '2027-01-10',
            'effective_end_date' => '2027-01-10',
        ];
        $employee->assignments()->create($assignment);
        $employee->assignments()->create([
            ...$assignment,
            'position_id' => Position::create(['name' => 'Jabatan Mendatang', 'code' => 'FUTURE'])->id,
            'effective_start_date' => '2027-01-11',
            'effective_end_date' => null,
        ]);

        $this->get(route('admin.employees.inactive'))->assertOk()
            ->assertSee('Ayu Nonaktif')->assertSee('Budi Resign')
            ->assertSee('Nonaktif sejak 10 Januari 2027')->assertSee('Jabatan Hari Ini · Kantor Hari Ini')
            ->assertDontSee('Jabatan Mendatang')
            ->assertDontSee('Citra Aktif')->assertDontSee('Doni Mendatang')
            ->assertDontSee('Eka Berakhir')->assertDontSee('Fani Tanpa Periode');
        $this->get(route('admin.employees.inactive', ['search' => 'ayu']))->assertOk()
            ->assertSee('Ayu Nonaktif')->assertDontSee('Budi Resign');
        $this->get(route('admin.employees.index'))->assertOk()
            ->assertSee('Aktif sejak 1 Januari 2027')->assertSee('Jabatan Hari Ini · Kantor Hari Ini')
            ->assertSee('Tidak ada periode kepegawaian berlaku')->assertSee('Tidak ada penempatan berlaku')
            ->assertDontSee('Jabatan Mendatang');
    }

    public function test_access_is_limited_to_active_hr_and_super_admins(): void
    {
        $index = route('admin.employees.index');
        $this->get($index)->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create()->refresh());
        $this->get($index)->assertForbidden();
        $this->post(route('admin.employees.store'), Employee::factory()->raw())->assertForbidden();
    }
}
