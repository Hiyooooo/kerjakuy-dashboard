<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    public function paginate(): LengthAwarePaginator
    {
        return Permission::withCount('roles')->orderBy('name')->paginate(10);
    }

    public function create(array $data): Permission
    {
        return Permission::create($data);
    }

    public function update(array $data, Permission $permission): Permission
    {
        return DB::transaction(function () use ($data, $permission): Permission {
            $permission = Permission::query()->lockForUpdate()->findOrFail($permission->getKey());
            abort_if($permission->isProtected(), 403);
            $permission->update($data);

            return $permission;
        });
    }

    public function delete(Permission $permission): void
    {
        DB::transaction(function () use ($permission): void {
            $permission = Permission::query()->lockForUpdate()->findOrFail($permission->getKey());
            abort_if($permission->isProtected(), 403);
            $permission->delete();
        });
    }
}
