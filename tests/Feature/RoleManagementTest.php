<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_delete_an_unused_role(): void
    {
        $this->signInAsAdmin();
        $this->get(route('admin.roles.index'))->assertOk()->assertSee('Role bawaan');
        $this->get(route('admin.roles.create'))->assertOk()->assertSee('Tambah role');

        $this->post(route('admin.roles.store'), ['name' => 'HR', 'description' => 'Tim HR'])
            ->assertRedirectToRoute('admin.roles.index');
        $role = Role::where('name', 'HR')->firstOrFail();

        $this->get(route('admin.roles.index'))->assertOk()->assertSee('Tim HR');
        $this->post(route('admin.roles.store'), ['name' => 'HR'])->assertSessionHasErrors('name');
        $this->get(route('admin.roles.edit', $role))->assertOk()->assertSee('Tim HR');
        $this->put(route('admin.roles.update', $role), ['name' => 'HR', 'description' => 'HR internal'])
            ->assertRedirectToRoute('admin.roles.index');
        $this->assertSame('HR internal', $role->fresh()->description);

        $this->delete(route('admin.roles.destroy', $role))->assertRedirectToRoute('admin.roles.index');
        $this->assertModelMissing($role);
    }

    public function test_role_routes_require_permission_and_protect_the_system_role(): void
    {
        $this->get(route('admin.roles.index'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create()->refresh())
            ->get(route('admin.roles.index'))->assertForbidden();

        $adminRole = $this->signInAsAdmin();
        $this->get(route('admin.roles.edit', $adminRole))->assertForbidden();
        $this->put(route('admin.roles.update', $adminRole), ['name' => 'other'])->assertForbidden();
        $this->delete(route('admin.roles.destroy', $adminRole))->assertForbidden();
        $this->post(route('admin.roles.store'), ['name' => 'ADMIN'])->assertSessionHasErrors('name');
        $this->assertModelExists($adminRole);
    }

    public function test_roles_with_users_or_permissions_cannot_be_deleted(): void
    {
        $adminRole = $this->signInAsAdmin();
        $assigned = Role::create(['name' => 'Assigned']);
        $withPermission = Role::create(['name' => 'With permission']);
        User::factory()->create()->roles()->attach($assigned);
        $withPermission->permissions()->attach($adminRole->permissions()->firstOrFail());

        foreach ([$assigned, $withPermission] as $role) {
            $this->from(route('admin.roles.edit', $role))->delete(route('admin.roles.destroy', $role))
                ->assertRedirectToRoute('admin.roles.edit', $role)
                ->assertSessionHasErrors('role');
            $this->assertModelExists($role);
        }
    }

    private function signInAsAdmin(): Role
    {
        $this->seed(AdminAccessSeeder::class);
        $role = Role::where('name', 'admin')->firstOrFail();
        $admin = User::factory()->create();
        $admin->roles()->attach($role);
        $this->actingAs($admin->refresh())->withoutVite();

        return $role;
    }
}
