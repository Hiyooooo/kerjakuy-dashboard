<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
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
        $this->get(route('admin.accounts.create'))->assertOk()->assertSee('Tambah pengguna');

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
