<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermissionsRequest;
use App\Http\Requests\UpdatePermissionsRequest;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissions) {}

    public function index(): View
    {
        return view('admin.permissions.index', ['permissions' => $this->permissions->paginate()]);
    }

    public function create(): View
    {
        return view('admin.permissions.form', ['permission' => new Permission]);
    }

    public function store(StorePermissionsRequest $request): RedirectResponse
    {
        $this->permissions->create($request->validated());

        return to_route('admin.permissions.index')->with('permission_status', 'Permission berhasil ditambahkan.');
    }

    public function edit(Permission $permission): View
    {
        abort_if($permission->isProtected(), 403);

        return view('admin.permissions.form', ['permission' => $permission->loadCount('roles')]);
    }

    public function update(UpdatePermissionsRequest $request, Permission $permission): RedirectResponse
    {
        $this->permissions->update($request->validated(), $permission);

        return to_route('admin.permissions.index')->with('permission_status', 'Permission berhasil diperbarui.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        $this->permissions->delete($permission);

        return to_route('admin.permissions.index')->with('permission_status', 'Permission berhasil dihapus.');
    }
}
