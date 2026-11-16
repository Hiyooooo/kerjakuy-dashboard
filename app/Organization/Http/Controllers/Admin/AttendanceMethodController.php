<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\StoreAttendanceMethodRequest;
use App\Organization\Http\Requests\UpdateAttendanceMethodRequest;
use App\Organization\Models\AttendanceMethod;
use App\Organization\Services\AttendanceMethodService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AttendanceMethodController extends Controller
{
    public function __construct(private readonly AttendanceMethodService $methods) {}

    public function index(): View
    {
        return view('admin.organization.index', [
            'requirements' => AttendanceMethod::REQUIREMENTS,
            'records' => $this->methods->paginate(),
            'resource' => 'attendance-methods',
            'label' => 'Metode absen',
        ]);
    }

    public function create(): View
    {
        return $this->form(new AttendanceMethod);
    }

    public function store(StoreAttendanceMethodRequest $request): RedirectResponse
    {
        $this->methods->create($request->validated());

        return to_route('admin.attendance-methods.index')->with('organization_status', 'Metode absen berhasil ditambahkan.');
    }

    public function edit(AttendanceMethod $attendanceMethod): View
    {
        return $this->form($attendanceMethod);
    }

    public function update(UpdateAttendanceMethodRequest $request, AttendanceMethod $attendanceMethod): RedirectResponse
    {
        $this->methods->update($request->safe()->except('edit_version'), $attendanceMethod, $request->string('edit_version')->toString());

        return to_route('admin.attendance-methods.index')->with('organization_status', 'Metode absen berhasil diperbarui.');
    }

    private function form(AttendanceMethod $record): View
    {
        return view('admin.organization.form', [
            'record' => $record,
            'resource' => 'attendance-methods',
            'label' => 'Metode absen',
            'nameLimit' => 100,
            'requirements' => AttendanceMethod::REQUIREMENTS,
        ]);
    }
}
