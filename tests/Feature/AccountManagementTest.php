<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->get(route('admin.accounts.create'))->assertOk()->assertSee('Tambah pengguna');

        $data = [
            'name' => 'Pengguna Baru',
            'email' => 'new-user@example.test',
            'status' => 'active',
            'password' => 'example-password',
            'password_confirmation' => 'example-password',
            'role_id' => $manager->roles()->firstOrFail()->id,
        ];
        $this->post(route('admin.accounts.store'), $data)->assertRedirectToRoute('admin.accounts.index');
        $account = User::where('email', $data['email'])->firstOrFail();
        $this->assertTrue(Hash::check($data['password'], $account->password));
        $this->assertFalse($account->roles()->exists());
        $this->get(route('admin.accounts.edit', $account))->assertOk()->assertSee($account->email);

        $this->put(route('admin.accounts.update', $account), [
            'name' => 'Nama Baru', 'email' => $account->email, 'status' => 'inactive', 'password' => '',
        ])->assertRedirectToRoute('admin.accounts.index');
        $account->refresh();
        $this->assertSame('Nama Baru', $account->name);
        $this->assertSame('inactive', $account->status);
        $this->assertTrue(Hash::check($data['password'], $account->password));

        $this->delete(route('admin.accounts.destroy', $account))->assertRedirectToRoute('admin.accounts.index');
        $this->assertModelMissing($account);
    }

    public function test_account_routes_require_permission_and_protect_role_holders(): void
    {
        $this->get(route('admin.accounts.index'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create())->get(route('admin.accounts.index'))->assertForbidden();

        $manager = $this->manager();
        $roleHolder = User::factory()->create();
        $roleHolder->roles()->attach(Role::where('name', 'admin')->firstOrFail());
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
        ])->assertSessionHasErrors(['email', 'status', 'password']);
        $this->assertDatabaseCount('users', 1);
    }

    private function manager(): User
    {
        $this->seed(AdminAccessSeeder::class);
        $manager = User::factory()->create();
        $manager->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        return $manager->refresh();
    }
}
