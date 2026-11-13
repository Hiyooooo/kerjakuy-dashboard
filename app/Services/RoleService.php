<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function paginate(): LengthAwarePaginator
    {
        return Role::withCount(['users', 'permissions'])->orderBy('name')->paginate(10);
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(array $data, Role $role): Role
    {
        $role->update($data);

        return $role;
    }

    public function delete(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $role = Role::query()->lockForUpdate()->findOrFail($role->getKey());
            abort_if($role->isSystemRole(), 403);

            if ($role->users()->exists() || $role->permissions()->exists()) {
                throw ValidationException::withMessages(['role' => 'Role masih terhubung ke pengguna atau permission.']);
            }

            $role->delete();
        });
    }
}
