<?php

use App\Access\Http\Controllers\Admin\AccountController;
use App\Access\Http\Controllers\Admin\AuthenticationController;
use App\Employee\EmployeeHistory;
use App\Employee\Http\Controllers\Admin\EmployeeController;
use App\Employee\Http\Controllers\Admin\EmployeeEmploymentPeriodController;
use App\Employee\Http\Controllers\Admin\EmployeeFaceProfileController;
use App\Employee\Http\Controllers\Admin\EmployeeFamilyMemberController;
use App\Employee\Http\Controllers\Admin\EmployeeHistoryController;
use App\Employee\Http\Controllers\Admin\EmployeeIdentityController;
use App\Employee\Http\Controllers\Admin\EmployeeJobAssignmentController;
use App\Employee\Http\Controllers\Admin\LateRequestController;
use App\Employee\Http\Controllers\Admin\LeaveRequestController;
use App\Employee\Http\Controllers\Admin\RequestApprovalController;
use App\Employee\Http\Controllers\Admin\ShiftRequestController;
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
use App\Scheduling\Http\Controllers\Admin\AttendanceRecordController;
use App\Scheduling\Http\Controllers\Admin\EmployeeDeviceLockController;
use App\Scheduling\Http\Controllers\Admin\EmployeeScheduleController;
use App\Scheduling\Http\Controllers\Admin\FaceRecognitionLogController;
use App\Scheduling\Http\Controllers\Admin\RegisteredDeviceController;
use App\Scheduling\Http\Controllers\Admin\WorkScheduleTemplateController;
use App\Scheduling\Http\Controllers\Admin\WorkShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.attendance.manage'])->group(function (): void {
    Route::get('face-verification-logs', [FaceRecognitionLogController::class, 'index'])->name('face-verification-logs.index');
    Route::get('face-verification-logs/{faceRecognitionLog}/photo', [FaceRecognitionLogController::class, 'photo'])
        ->middleware('can:admin.attendance.photos.view')->name('face-verification-logs.photo');
    Route::resource('attendance-records', AttendanceRecordController::class)
        ->parameters(['attendance-records' => 'attendanceRecord'])->only(['index', 'create', 'store', 'show']);
    Route::patch('attendance-records/{attendanceRecord}/check-out', [AttendanceRecordController::class, 'checkOut'])->name('attendance-records.checkout');
    Route::get('attendance-records/{attendanceRecord}/selfie/{event}', [AttendanceRecordController::class, 'photo'])
        ->whereIn('event', ['masuk', 'pulang'])->middleware('can:admin.attendance.photos.view')->name('attendance-records.photo');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.devices.manage'])->group(function (): void {
    Route::resource('registered-devices', RegisteredDeviceController::class)->only(['index', 'store']);
    Route::patch('registered-devices/{registeredDevice}/block', [RegisteredDeviceController::class, 'block'])->name('registered-devices.block');
    Route::prefix('registered-devices/{registeredDevice}/locks')->name('registered-devices.locks.')->scopeBindings()
        ->controller(EmployeeDeviceLockController::class)->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('employees', 'employees')->name('employees');
            Route::post('/', 'store')->name('store');
            Route::patch('{deviceLock}/release', 'release')->name('release');
        });
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'can:admin.scheduling.manage'])->group(function (): void {
    Route::resource('work-shifts', WorkShiftController::class)->except(['show', 'destroy']);
    Route::resource('work-schedule-templates', WorkScheduleTemplateController::class)->except(['show', 'destroy']);
    Route::prefix('employees/{employee}')->name('employees.')->scopeBindings()->controller(EmployeeScheduleController::class)->group(function (): void {
        Route::get('schedules', 'index')->name('schedules.index');
        Route::get('schedule-assignments/create', 'createDefault')->name('schedule-assignments.create');
        Route::post('schedule-assignments', 'storeDefault')->name('schedule-assignments.store');
        Route::get('schedule-assignments/{scheduleAssignment}/edit', 'editDefault')->name('schedule-assignments.edit');
        Route::put('schedule-assignments/{scheduleAssignment}', 'updateDefault')->name('schedule-assignments.update');
        Route::get('shift-overrides/create', 'createOverride')->name('shift-overrides.create');
        Route::post('shift-overrides', 'storeOverride')->name('shift-overrides.store');
        Route::get('shift-overrides/{shiftOverride}/edit', 'editOverride')->name('shift-overrides.edit');
        Route::put('shift-overrides/{shiftOverride}', 'updateOverride')->name('shift-overrides.update');
    });
});

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
    Route::get('request-approvals', [RequestApprovalController::class, 'index'])->name('request-approvals.index');
    Route::post('request-approvals/{record}', [RequestApprovalController::class, 'decide'])
        ->whereNumber('record')->name('request-approvals.decide');
    foreach (['overtime-requests', 'shift-swap-requests'] as $prefix) {
        Route::prefix($prefix)->name($prefix.'.')->controller(ShiftRequestController::class)->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('{record}', 'show')->whereNumber('record')->name('show');
        });
    }
    Route::post('shift-swap-requests/{record}/approve', [ShiftRequestController::class, 'approve'])
        ->whereNumber('record')->name('shift-swap-requests.approve');
    Route::resource('late-requests', LateRequestController::class)->parameters(['late-requests' => 'lateRequest'])
        ->only(['index', 'create', 'store', 'show']);
    Route::get('late-requests/{lateRequest}/attachment', [LateRequestController::class, 'attachment'])
        ->whereNumber('lateRequest')->name('late-requests.attachment');
    Route::prefix('leave-requests')->name('leave-requests.')->controller(LeaveRequestController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('employees', 'employees')->name('employees');
        Route::get('balance', 'balance')->name('balance');
        Route::get('balances', 'balancePage')->name('balances');
        Route::post('balances/adjustments', 'storeAdjustment')->name('balances.adjustments.store');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('entitlements/create', 'createEntitlement')->name('entitlements.create');
        Route::post('entitlements', 'storeEntitlement')->name('entitlements.store');
        Route::get('{leaveRequest}', 'show')->whereNumber('leaveRequest')->name('show');
        Route::get('{leaveRequest}/attachment', 'attachment')->whereNumber('leaveRequest')->name('attachment');
    });
    Route::get('employees/inactive', [EmployeeController::class, 'inactive'])->name('employees.inactive');
    Route::resource('employees', EmployeeController::class)->except(['show', 'destroy']);
    Route::get('employees/{employee}/identity', [EmployeeIdentityController::class, 'edit'])->name('employees.identity.edit');
    Route::put('employees/{employee}/identity', [EmployeeIdentityController::class, 'update'])->name('employees.identity.update');
    Route::resource('employees.family-members', EmployeeFamilyMemberController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])->scoped(['family_member' => 'id']);
    foreach (EmployeeHistory::cases() as $history) {
        Route::prefix('employees/{employee}/'.$history->value)->name('employees.'.$history->value.'.')
            ->controller(EmployeeHistoryController::class)->group(function () use ($history): void {
                Route::get('/', 'index')->defaults('history', $history->value)->name('index');
                Route::get('create', 'create')->defaults('history', $history->value)->name('create');
                Route::post('/', 'store')->defaults('history', $history->value)->name('store');
                Route::get('{record}/edit', 'edit')->whereNumber('record')->defaults('history', $history->value)->name('edit');
                Route::put('{record}', 'update')->whereNumber('record')->defaults('history', $history->value)->name('update');
                if ($history === EmployeeHistory::Training) {
                    Route::get('{record}/certificate', 'certificate')->whereNumber('record')->name('certificate');
                }
            });
    }
    Route::resource('employees.periods', EmployeeEmploymentPeriodController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])->scoped(['period' => 'id']);
    Route::resource('employees.assignments', EmployeeJobAssignmentController::class)
        ->only(['index', 'create', 'store', 'edit', 'update'])->scoped(['assignment' => 'id']);
    Route::resource('employees.face-profiles', EmployeeFaceProfileController::class)->only(['index', 'store']);
    Route::get('employees/{employee}/face-profiles/{faceProfile}/photo', [EmployeeFaceProfileController::class, 'photo'])
        ->scopeBindings()->name('employees.face-profiles.photo');
});
