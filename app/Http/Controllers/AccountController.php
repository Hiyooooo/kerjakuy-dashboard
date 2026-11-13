<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts) {}

    public function index(): View
    {
        return view('admin.accounts.index', ['accounts' => $this->accounts->paginate()]);
    }

    public function create(): View
    {
        return $this->form(new User);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $this->accounts->create($request->validated());

        return to_route('admin.accounts.index')->with('account_status', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('admin.accounts.change', $user);

        return $this->form($user);
    }

    public function update(UpdateAccountRequest $request, User $user): RedirectResponse
    {
        $this->accounts->update($request->validated(), $user);

        return to_route('admin.accounts.index')->with('account_status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('admin.accounts.change', $user);
        $this->accounts->delete($user);

        return to_route('admin.accounts.index')->with('account_status', 'Pengguna berhasil dihapus.');
    }

    private function form(User $account): View
    {
        return view('admin.accounts.form', [
            'account' => $account,
            'roles' => Role::whereIn('code', Role::ASSIGNABLE)->orderBy('name')->get(),
        ]);
    }
}
