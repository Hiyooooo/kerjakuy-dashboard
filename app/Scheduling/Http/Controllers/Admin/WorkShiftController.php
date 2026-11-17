<?php

namespace App\Scheduling\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Models\AttendanceTime;
use App\Scheduling\Http\Requests\SaveWorkShiftRequest;
use App\Scheduling\Models\WorkShift;
use App\Scheduling\Services\WorkShiftService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class WorkShiftController extends Controller
{
    public function __construct(private readonly WorkShiftService $master) {}

    public function index(): View
    {
        return view('admin.scheduling.index', [
            'records' => WorkShift::with('attendanceTime')->orderBy('name')->orderBy('id')->paginate(15),
            'resource' => 'work-shifts',
            'label' => 'Shift kerja',
        ]);
    }

    public function create(): View
    {
        return $this->edit(new WorkShift);
    }

    public function store(SaveWorkShiftRequest $request): RedirectResponse
    {
        return $this->update($request, new WorkShift);
    }

    public function edit(WorkShift $workShift): View
    {
        return view('admin.scheduling.form', [
            'record' => $workShift,
            'resource' => 'work-shifts',
            'label' => 'Shift kerja',
            'options' => AttendanceTime::orderBy('name')->get(),
        ]);
    }

    public function update(SaveWorkShiftRequest $request, WorkShift $workShift): RedirectResponse
    {
        try {
            $this->master->save($workShift, $request->safe()->except('edit_version'), $request->validated('edit_version'));
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.work-shifts.index')->with('scheduling_status', 'Shift kerja tersimpan.');
    }
}
