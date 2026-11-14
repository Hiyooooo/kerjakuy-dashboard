<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\StoreAttendanceTimeRequest;
use App\Organization\Http\Requests\UpdateAttendanceTimeRequest;
use App\Organization\Models\AttendanceTime;
use App\Organization\Services\AttendanceTimeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AttendanceTimeController extends Controller
{
    public function __construct(private readonly AttendanceTimeService $attendanceTimes) {}

    public function index(): View
    {
        return view('admin.organization.index', [
            'records' => $this->attendanceTimes->paginate(),
            'resource' => 'attendance-times',
            'label' => 'Waktu absen',
        ]);
    }

    public function create(): View
    {
        return $this->form(new AttendanceTime);
    }

    public function store(StoreAttendanceTimeRequest $request): RedirectResponse
    {
        $this->attendanceTimes->create($request->validated());

        return to_route('admin.attendance-times.index')->with('organization_status', 'Waktu absen berhasil ditambahkan.');
    }

    public function edit(AttendanceTime $attendanceTime): View
    {
        return $this->form($attendanceTime);
    }

    public function update(UpdateAttendanceTimeRequest $request, AttendanceTime $attendanceTime): RedirectResponse
    {
        $this->attendanceTimes->update($request->validated(), $attendanceTime);

        return to_route('admin.attendance-times.index')->with('organization_status', 'Waktu absen berhasil diperbarui.');
    }

    public function destroy(AttendanceTime $attendanceTime): RedirectResponse
    {
        $this->attendanceTimes->delete($attendanceTime);

        return to_route('admin.attendance-times.index')->with('organization_status', 'Waktu absen berhasil dihapus.');
    }

    private function form(AttendanceTime $record): View
    {
        return view('admin.organization.form', [
            'record' => $record,
            'resource' => 'attendance-times',
            'label' => 'Waktu absen',
        ]);
    }
}
