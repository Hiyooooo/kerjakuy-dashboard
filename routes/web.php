<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticationController::class, 'show'])->name('login');
    Route::post('/login', [AuthenticationController::class, 'login'])->name('login.store');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.roles.manage'])->group(function (): void {
    Route::resource('roles', RoleController::class)->except('show');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.permissions.manage'])->group(function (): void {
    Route::resource('permissions', PermissionController::class)->except('show');
});

Route::post('/logout', [AuthenticationController::class, 'logout'])
    ->middleware('auth')->name('logout');

Route::get('/', DashboardController::class)
    ->middleware(['auth', 'can:admin.dashboard.view'])
    ->name('admin.dashboard');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.accounts.manage'])->group(function (): void {
    Route::resource('accounts', AccountController::class)
        ->except('show')
        ->parameters(['accounts' => 'user']);
});
