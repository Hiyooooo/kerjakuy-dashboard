<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuthenticationController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticationController::class, 'show'])->name('login');
    Route::post('/login', [AuthenticationController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthenticationController::class, 'logout'])
    ->middleware('auth')->name('logout');

Route::get('/', DashboardController::class)
    ->middleware(['auth', 'can:admin.dashboard.view'])
    ->name('admin.dashboard');
