<?php

namespace Tests\Feature\Access;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use Database\Seeders\AdminAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_update_and_delete_an_account(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->withoutVite();

        $this->get(route('admin.accounts.index'))->assertOk()->assertSee($manager->email);
        $this->get(route('admin.accounts.create'))->assertOk()->assertSee('Tambah akun');

        $data = [
            'name' => 'Pengguna Baru',
            'email' => 'new-user@example.test',
            'status' => 'active',
            'password' => 'example-password',
            'password_confirmation' => 'example-password',
            'role_id' => Role::where('code', 'hrd_admin')->firstOrFail()->id,
        ];
        $this->post(route('admin.accounts.store'), $data)->assertRedirectToRoute('admin.accounts.index');
        $account = User::where('email', $data['email'])->firstOrFail();
        $this->assertTrue(Hash::check($data['password'], $account->password));
        $this->assertSame('hrd_admin', $account->role->code);
        $this->get(route('admin.accounts.edit', $account))->assertOk()->assertSee($account->email);

        $this->put(route('admin.accounts.update', $account), [
            'name' => 'Nama Baru', 'email' => $account->email, 'status' => 'inactive', 'password' => '', 'role_id' => $account->role_id,
        ])->assertRedirectToRoute('admin.accounts.index');
        $account->refresh();
        $this->assertSame('Nama Baru', $account->name);
        $this->assertSame('inactive', $account->status);
        $this->assertTrue(Hash::check($data['password'], $account->password));

        $this->delete(route('admin.accounts.destroy', $account))->assertRedirectToRoute('admin.accounts.index');
        $this->assertModelMissing($account);
    }

    public function test_manager_can_link_and_unlink_an_employee_without_overwriting_a_newer_link(): void
    {
        $this->actingAs($this->manager())->withoutVite();
        $employee = Employee::factory()->create();
        $account = User::factory()->create()->refresh();
        $other = User::factory()->create()->refresh();
        $update = route('admin.accounts.update', $account);
        $data = $account->only(['name', 'email', 'status', 'role_id']);
        $initial = $account->employeeLinkVersion();

        $this->get(route('admin.accounts.edit', $account))->assertOk()->assertSee($employee->employee_code)->assertSee($initial);
        $this->put($update, [...$data, 'employee_id' => $employee->id, 'employee_link_version' => $initial])
            ->assertRedirectToRoute('admin.accounts.index');
        $this->assertSame($account->id, $employee->user()->sole()->id);
        $this->get(route('admin.accounts.index'))->assertOk()->assertSee($employee->employee_code);
        $this->put(route('admin.accounts.update', $other), [
            ...$other->only(['name', 'email', 'status', 'role_id']),
            'employee_id' => $employee->id,
            'employee_link_version' => $other->employeeLinkVersion(),
        ])->assertSessionHasErrors('employee_id');
        $this->put($update, [...$data, 'employee_id' => '', 'employee_link_version' => $initial])
            ->assertSessionHasErrors('employee_link_version');
        $this->assertSame($employee->id, $account->refresh()->employee_id);

        $this->put($update, [...$data, 'employee_id' => '', 'employee_link_version' => $account->employeeLinkVersion()])
            ->assertRedirectToRoute('admin.accounts.index');
        $this->assertNull($account->refresh()->employee_id);
    }

    public function test_account_search_filters_before_pagination_and_keeps_the_query(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->withoutVite();
        User::factory()->create(['name' => 'Pencarian Nama Lama']);
        User::factory()->count(10)->create(['name' => 'Pencarian Nama Baru']);
        User::factory()->create(['email' => 'alamat-khusus@example.test']);
        User::factory()->create(['role_id' => $manager->role_id]);
        $index = route('admin.accounts.index');

        $this->get($index)->assertOk()->assertSee('Akun Super Admin');
        $this->get(route('admin.accounts.index', ['search' => $manager->email]))->assertOk()->assertSee('Akun Anda');
        $this->get($index.'?search=pencarian')->assertOk()->assertSee('Pencarian Nama Baru')
            ->assertDontSee('Pencarian Nama Lama')->assertSee('search=pencarian&amp;page=2', false);
        $this->get($index.'?search=pencarian&page=2')->assertOk()->assertSee('Pencarian Nama Lama');
        $this->get($index.'?search=ALAMAT-KHUSUS')->assertOk()
            ->assertSee('alamat-khusus@example.test')->assertDontSee('Pencarian Nama Baru');
    }

    public function test_failed_account_form_only_restores_input_on_its_redirect(): void
    {
        $this->actingAs($this->manager())->withoutVite();
        $employee = Employee::factory()->create();
        $create = route('admin.accounts.create');
        $store = route('admin.accounts.store');
        $data = ['name' => 'Isian dari tab pertama', 'employee_id' => (string) $employee->id];

        $this->post($store, $data)->assertSessionHasErrors('email');
        $this->get($create)->assertOk()->assertDontSee($data['name'])
            ->assertDontSee('id="email-error"', false)
            ->assertDontSee('value="'.$employee->id.'" selected', false);

        $this->followingRedirects()->post($store, $data)->assertOk()->assertSee($data['name'])
            ->assertSee('id="email-error"', false)
            ->assertSee('value="'.$employee->id.'" selected', false);
    }

    public function test_account_routes_require_permission_and_protect_role_holders(): void
    {
        $this->get(route('admin.accounts.index'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create())->get(route('admin.accounts.index'))->assertForbidden();

        $manager = $this->manager();
        $roleHolder = User::factory()->create(['role_id' => $manager->role_id]);
        $this->actingAs($manager)->get(route('admin.accounts.edit', $roleHolder))->assertForbidden();
        $this->put(route('admin.accounts.update', $roleHolder))->assertForbidden();
        $this->delete(route('admin.accounts.destroy', $manager))->assertForbidden();
    }

    public function test_invalid_account_data_is_rejected(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->post(route('admin.accounts.store'), [
            'name' => 'Pengguna Baru',
            'email' => $manager->email,
            'status' => 'unknown',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors(['email', 'status', 'password', 'role_id']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_super_admin_role_cannot_be_assigned_through_account_management(): void
    {
        $manager = $this->manager();
        $account = User::factory()->create()->refresh();

        $this->actingAs($manager)->put(route('admin.accounts.update', $account), [
            'name' => $account->name,
            'email' => $account->email,
            'status' => 'active',
            'role_id' => $manager->role_id,
        ])->assertSessionHasErrors('role_id');

        $this->assertSame('attendance_admin', $account->fresh()->role->code);
    }

    public function test_password_reset_revokes_only_the_target_accounts_sessions(): void
    {
        config(['session.driver' => 'database']);
        $manager = $this->manager();
        $account = User::factory()->create()->refresh();

        foreach (['target-session' => $account, 'other-session' => $manager] as $id => $user) {
            DB::table('sessions')->insert([
                'id' => $id,
                'user_id' => $user->id,
                'payload' => '',
                'last_activity' => now()->timestamp,
            ]);
        }

        $this->actingAs($manager)->put(route('admin.accounts.update', $account), [
            'name' => $account->name,
            'email' => $account->email,
            'status' => $account->status,
            'role_id' => $account->role_id,
            'password' => 'replacement-password',
            'password_confirmation' => 'replacement-password',
        ])->assertRedirectToRoute('admin.accounts.index');

        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'other-session']);
        $this->assertNull($account->fresh()->remember_token);
    }

    private function manager(): User
    {
        $this->seed(AdminAccessSeeder::class);
        $manager = User::factory()->create(['role_id' => Role::where('code', 'super_admin')->firstOrFail()->id]);

        return $manager->refresh();
    }
}
