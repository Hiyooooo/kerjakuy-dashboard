<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_an_unused_permission(): void
    {
        $this->signInAsAdmin();
        $this->get(route('admin.permissions.index'))->assertOk()->assertSee('admin.dashboard.view');
        $this->get(route('admin.permissions.create'))->assertOk()->assertSee('Tambah permission');

        $this->post(route('admin.permissions.store'), ['name' => 'employees.view'])
            ->assertRedirectToRoute('admin.permissions.index');
        $permission = Permission::where('name', 'employees.view')->firstOrFail();
        $this->post(route('admin.permissions.store'), ['name' => 'employees.view'])->assertSessionHasErrors('name');
        $this->post(route('admin.permissions.store'), ['name' => 'Invalid Name'])->assertSessionHasErrors('name');
        $this->get(route('admin.permissions.edit', $permission))->assertOk()->assertSee('employees.view');

        $this->put(route('admin.permissions.update', $permission), ['name' => 'employees.view', 'description' => 'Lihat pegawai'])
            ->assertRedirectToRoute('admin.permissions.index');
        $this->assertSame('Lihat pegawai', $permission->fresh()->description);
        $this->delete(route('admin.permissions.destroy', $permission))->assertRedirectToRoute('admin.permissions.index');
        $this->assertModelMissing($permission);
    }

    public function test_permission_routes_require_access_and_protect_built_in_and_assigned_permissions(): void
    {
        $this->get(route('admin.permissions.index'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create()->refresh())
            ->post(route('admin.permissions.store'), ['name' => 'employees.view'])->assertForbidden();

        $adminRole = $this->signInAsAdmin();
        $builtIn = Permission::where('name', 'admin.dashboard.view')->firstOrFail();
        $adminRole->permissions()->detach($builtIn);
        $assigned = Permission::create(['name' => 'employees.edit']);
        $adminRole->permissions()->attach($assigned);

        foreach ([$builtIn, $assigned] as $permission) {
            $this->get(route('admin.permissions.edit', $permission))->assertForbidden();
            $this->put(route('admin.permissions.update', $permission), ['name' => 'employees.view'])->assertForbidden();
            $this->delete(route('admin.permissions.destroy', $permission))->assertForbidden();
            $this->assertModelExists($permission);
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
