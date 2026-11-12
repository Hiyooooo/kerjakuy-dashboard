<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
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
            && $user->roles()->whereHas(
                'permissions',
                fn (Builder $permissions) => $permissions->where('permissions.name', 'admin.dashboard.view'),
            )->exists());
    }
}
