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
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceRecordController extends Controller
{
    public function index(): View
    {
        return view('admin.scheduling.attendance.index', [
            'records' => AttendanceRecord::with('employee')->orderByDesc('work_date')->orderByDesc('id')->paginate(20),
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
