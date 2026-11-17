<?php

namespace App\Access\Http\Controllers\Admin;

use App\Access\Http\Requests\StoreAccountRequest;
use App\Access\Http\Requests\UpdateAccountRequest;
use App\Access\Models\Role;
use App\Access\Models\User;
use App\Access\Services\AccountService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts) {}

    public function index(Request $request): View
    {
        $value = $request->query('search');
        $search = is_string($value) ? Str::substr(trim($value), 0, 150) : '';

        return view('admin.accounts.index', [
            'accounts' => $this->accounts->paginate(search: $search),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return $this->form(new User);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        try {
            $this->accounts->create($request->validated());
        } catch (ValidationException $exception) {
            throw $request->forAccountForm($exception);
        }

        return to_route('admin.accounts.index')->with('account_status', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        Gate::authorize('admin.accounts.change', $user);

        return $this->form($user);
    }

    public function update(UpdateAccountRequest $request, User $user): RedirectResponse
    {
        try {
            $this->accounts->update($request->safe()->except('employee_link_version'), $user, $request->validated('employee_link_version'));
        } catch (ValidationException $exception) {
            throw $request->forAccountForm($exception);
        }

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
            'employees' => $this->accounts->linkableEmployees($account),
        ]);
    }
}
