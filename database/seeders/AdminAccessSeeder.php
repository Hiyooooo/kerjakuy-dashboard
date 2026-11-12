<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin']);
        foreach (['admin.dashboard.view', 'admin.accounts.manage'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name]);
            $role->permissions()->syncWithoutDetaching([$permission->getKey()]);
        }
    }
}
