<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    public function index(): View
    {
        return view('admin.roles.index', ['roles' => $this->roles->paginate()]);
    }

    public function create(): View
    {
        return view('admin.roles.form', ['role' => new Role]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roles->create($request->validated());

        return to_route('admin.roles.index')->with('role_status', 'Role berhasil ditambahkan.');
    }

    public function edit(Role $role): View
    {
        abort_if($role->isSystemRole(), 403);

        return view('admin.roles.form', ['role' => $role->loadCount(['users', 'permissions'])]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roles->update($request->validated(), $role);

        return to_route('admin.roles.index')->with('role_status', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->roles->delete($role);

        return to_route('admin.roles.index')->with('role_status', 'Role berhasil dihapus.');
    }
}
