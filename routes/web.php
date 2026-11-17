<?php

use App\Access\Http\Controllers\Admin\AccountController;
use App\Access\Http\Controllers\Admin\AuthenticationController;
use App\Employee\Http\Controllers\Admin\EmployeeController;
use App\Employee\Http\Controllers\Admin\EmployeeEmploymentPeriodController;
use App\Employee\Http\Controllers\Admin\EmployeeFaceProfileController;
use App\Employee\Http\Controllers\Admin\EmployeeIdentityController;
use App\Employee\Http\Controllers\Admin\EmployeeJobAssignmentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Organization\Http\Controllers\Admin\AttendanceLocationController;
use App\Organization\Http\Controllers\Admin\AttendanceMethodController;
use App\Organization\Http\Controllers\Admin\AttendanceTimeController;
use App\Organization\Http\Controllers\Admin\FaceTolerancePolicyController;
use App\Organization\Http\Controllers\Admin\HolidayController;
use App\Organization\Http\Controllers\Admin\JobDeskController;
use App\Organization\Http\Controllers\Admin\LeaveCategoryController;
use App\Organization\Http\Controllers\Admin\OfficeController;
use App\Organization\Http\Controllers\Admin\OutOfLocationReasonController;
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
    Route::resource('leave-categories', LeaveCategoryController::class)->except('show');
    Route::post('holidays/import', [HolidayController::class, 'import'])->middleware('throttle:6,1')->name('holidays.import');
    Route::resource('holidays', HolidayController::class)->except('show');
    Route::resource('out-of-location-reasons', OutOfLocationReasonController::class)->except('show');
    Route::resource('attendance-methods', AttendanceMethodController::class)->except(['show', 'destroy']);
    Route::resource('attendance-locations', AttendanceLocationController::class)->except(['show', 'destroy']);
    Route::resource('face-tolerance-policies', FaceTolerancePolicyController::class)->except(['show', 'destroy']);
    Route::get('face-tolerance-policies/{face_tolerance_policy}/replacement', [FaceTolerancePolicyController::class, 'replacement'])->name('face-tolerance-policies.replacement');
    Route::post('face-tolerance-policies/{face_tolerance_policy}/replacement', [FaceTolerancePolicyController::class, 'replace'])->name('face-tolerance-policies.replace');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.accounts.manage'])->group(function (): void {
    Route::resource('accounts', AccountController::class)
        ->except('show')
        ->parameters(['accounts' => 'user']);
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.employees.manage'])->group(function (): void {
    Route::get('employees/inactive', [EmployeeController::class, 'inactive'])->name('employees.inactive');
    Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);
    Route::get('employees/{employee}/identity', [EmployeeIdentityController::class, 'edit'])->name('employees.identity.edit');
    Route::put('employees/{employee}/identity', [EmployeeIdentityController::class, 'update'])->name('employees.identity.update');
    Route::resource('employees.periods', EmployeeEmploymentPeriodController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])->scoped(['period' => 'id']);
    Route::resource('employees.assignments', EmployeeJobAssignmentController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])->scoped(['assignment' => 'id']);
    Route::resource('employees.face-profiles', EmployeeFaceProfileController::class)->only(['index', 'store']);
    Route::get('employees/{employee}/face-profiles/{faceProfile}/photo', [EmployeeFaceProfileController::class, 'photo'])
        ->scopeBindings()->name('employees.face-profiles.photo');
});
