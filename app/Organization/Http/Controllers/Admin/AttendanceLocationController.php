<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\SaveAttendanceLocationRequest;
use App\Organization\Models\AttendanceLocation;
use App\Organization\Models\Office;
use App\Organization\Services\AttendanceLocationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AttendanceLocationController extends Controller
{
    public function __construct(private readonly AttendanceLocationService $locations) {}

    public function index(): View
    {
        return view('admin.organization.index', [
            'records' => AttendanceLocation::with('office')->orderBy('name')->orderBy('id')->paginate(15),
            'resource' => 'attendance-locations',
            'label' => 'Lokasi absen',
        ]);
    }

    public function create(): View
    {
        return $this->edit(new AttendanceLocation);
    }

    public function store(SaveAttendanceLocationRequest $request): RedirectResponse
    {
        return $this->update($request, new AttendanceLocation);
    }

    public function edit(AttendanceLocation $attendanceLocation): View
    {
        return view('admin.organization.form', [
            'record' => $attendanceLocation,
            'offices' => Office::orderBy('name')->orderBy('id')->get(['id', 'name', 'code', 'is_active']),
            'resource' => 'attendance-locations',
            'label' => 'Lokasi absen',
            'nameLimit' => 120,
        ]);
    }

    public function update(SaveAttendanceLocationRequest $request, AttendanceLocation $attendanceLocation): RedirectResponse
    {
        $this->locations->save($attendanceLocation, $request->safe()->except('edit_version'), $request->validated('edit_version'));

        return to_route('admin.attendance-locations.index')->with('organization_status', 'Lokasi absen tersimpan.');
    }
}
