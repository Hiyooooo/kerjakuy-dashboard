<?php

use App\Access\Http\Controllers\Admin\AccountController;
use App\Access\Http\Controllers\Admin\AuthenticationController;
use App\Http\Controllers\Admin\DashboardController;
use App\Organization\Http\Controllers\Admin\AttendanceTimeController;
use App\Organization\Http\Controllers\Admin\JobDeskController;
use App\Organization\Http\Controllers\Admin\OfficeController;
use App\Organization\Http\Controllers\Admin\PositionController;
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

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.organization.manage'])->group(function (): void {
    Route::resource('positions', PositionController::class)->except('show');
    Route::resource('offices', OfficeController::class)->except('show');
    Route::resource('job-desks', JobDeskController::class)->except('show');
    Route::resource('attendance-times', AttendanceTimeController::class)->except('show');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.accounts.manage'])->group(function (): void {
    Route::resource('accounts', AccountController::class)
        ->except('show')
        ->parameters(['accounts' => 'user']);
});
