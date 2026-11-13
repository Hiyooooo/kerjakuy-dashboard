<?php

namespace App\Providers;

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
        Gate::define('admin.dashboard.view', fn (User $user): bool => $user->hasPermission('admin.dashboard.view'));
        Gate::define('admin.accounts.manage', fn (User $user): bool => $user->hasPermission('admin.accounts.manage'));
        Gate::define('admin.roles.manage', fn (User $user): bool => $user->hasPermission('admin.roles.manage'));
        Gate::define('admin.permissions.manage', fn (User $user): bool => $user->hasPermission('admin.permissions.manage'));
        Gate::define('admin.accounts.change', fn (User $actor, User $account): bool => ! $actor->is($account)
            && ! $account->roles()->exists()
            && $actor->hasPermission('admin.accounts.manage'));
    }
}
