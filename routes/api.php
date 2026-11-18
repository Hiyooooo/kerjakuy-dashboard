<?php

use App\Scheduling\Http\Controllers\Api\AttendanceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/attendance')->name('api.v1.attendance.')
    ->middleware(['web', 'auth:web', 'can:employee.attendance.access', 'throttle:60,1'])
    ->controller(AttendanceController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('{recordId}', 'show')->whereNumber('recordId')->name('show');
        Route::post('check-in', 'unavailable')->name('check-in');
        Route::post('{recordId}/check-out', 'unavailable')->whereNumber('recordId')->name('check-out');
    });
