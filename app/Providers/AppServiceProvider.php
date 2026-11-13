<?php

namespace App\Providers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('admin.dashboard.view', fn (User $user): bool => $user->status === 'active'
            && in_array($user->role?->code, ['super_admin', ...Role::ASSIGNABLE], true));
        Gate::define('admin.accounts.manage', fn (User $user): bool => $user->status === 'active' && $user->isSuperAdmin());
        Gate::define('admin.accounts.change', fn (User $actor, User $account): bool => ! $actor->is($account)
            && ! $account->isSuperAdmin()
            && $actor->can('admin.accounts.manage'));
    }
}
