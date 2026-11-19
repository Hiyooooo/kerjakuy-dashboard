<?php

namespace App\Scheduling\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Models\AttendanceLocation;
use App\Organization\Models\AttendanceMethod;
use App\Organization\Models\OutOfLocationReason;
use App\Scheduling\Http\Requests\StoreAttendanceRecordRequest;
use App\Scheduling\Models\AttendanceRecord;
use App\Scheduling\Models\RegisteredDevice;
use App\Scheduling\Services\AttendanceRecordService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceRecordController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'date_from' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'date_to' => ['nullable', 'date_format:Y-m-d', $request->filled('date_from') ? 'after_or_equal:date_from' : 'after_or_equal:0001-01-01'],
            'missing_checkout' => ['nullable', 'boolean'],
        ]);
        $records = AttendanceRecord::with('employee:id,full_name,employee_code')
            ->when(($filters['search'] ?? '') !== '', fn ($query) => $query->whereHas('employee', fn ($employee) => $employee->matchingSearch($filters['search'])))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('work_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('work_date', '<=', $date))
            ->when($filters['missing_checkout'] ?? false, fn ($query) => $query->whereNotNull('check_in_at')->whereNull('check_out_at'));

        return view('admin.scheduling.attendance.index', [
            'records' => $records->orderByDesc('work_date')->orderByDesc('id')->paginate(20)->appends($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.scheduling.attendance.detail', [
            'record' => null,
            'methods' => AttendanceMethod::where('is_active', true)->where('requires_face_verification', false)->orderBy('name')->get(),
            'locations' => AttendanceLocation::where('is_active', true)->orderBy('name')->get(),
            'devices' => RegisteredDevice::where('status', 'registered')->orderBy('device_name')->get(),
            'reasons' => OutOfLocationReason::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function show(AttendanceRecord $attendanceRecord): View
    {
        return view('admin.scheduling.attendance.detail', [
            'record' => $attendanceRecord->load(['employee', 'shift', 'method', 'location', 'device', 'checkInReason', 'checkOutReason']),
            'verificationLogs' => $attendanceRecord->faceRecognitionLogs()->orderBy('captured_at')->orderBy('id')->get()->groupBy('verification_type'),
            'reasons' => OutOfLocationReason::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreAttendanceRecordRequest $request, AttendanceRecordService $service): RedirectResponse
    {
        try {
            $record = $service->checkIn($request->validated(), $request->user()->id);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('admin.attendance-records.create'));
        }

        return to_route('admin.attendance-records.show', $record)->with('attendance_status', 'Absensi masuk tercatat.');
    }

    public function checkOut(StoreAttendanceRecordRequest $request, AttendanceRecord $attendanceRecord, AttendanceRecordService $service): RedirectResponse
    {
        try {
            $service->checkOut($attendanceRecord, $request->validated(), $request->user()->id);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('admin.attendance-records.show', $attendanceRecord));
        }

        return to_route('admin.attendance-records.show', $attendanceRecord)->with('attendance_status', 'Absensi pulang tercatat.');
    }

    public function photo(AttendanceRecord $attendanceRecord, string $event): StreamedResponse
    {
        $path = $event === 'masuk' ? $attendanceRecord->check_in_selfie_path : $attendanceRecord->check_out_selfie_path;
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
