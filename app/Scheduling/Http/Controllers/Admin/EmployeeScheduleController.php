<?php

namespace App\Scheduling\Http\Controllers\Admin;

use App\Employee\Models\Employee;
use App\Http\Controllers\Controller;
use App\Organization\Models\Office;
use App\Scheduling\Http\Requests\SaveEmployeeScheduleRequest;
use App\Scheduling\Models\EmployeeShiftSchedule;
use App\Scheduling\Models\EmployeeWorkScheduleAssignment;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use App\Scheduling\Services\EmployeeScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeScheduleController extends Controller
{
    public function __construct(private readonly EmployeeScheduleService $schedules) {}

    public function index(Request $request, Employee $employee): View
    {
        $date = $request->validate(['date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:0001-01-01']])['date'] ?? today('Asia/Jakarta')->toDateString();

        return view('admin.scheduling.employee-index', [
            'employee' => $employee,
            'date' => $date,
            'effective' => $this->schedules->effectiveSchedule($employee, CarbonImmutable::parse($date, 'Asia/Jakarta')),
            'assignments' => $employee->scheduleAssignments()->with('template')->orderByDesc('effective_start_date')->paginate(10, ['*'], 'assignments')->appends(['date' => $date]),
            'overrides' => $employee->shiftOverrides()->with(['shift', 'office'])->orderByDesc('work_date')->paginate(10, ['*'], 'overrides')->appends(['date' => $date]),
        ]);
    }

    public function createDefault(Employee $employee): View
    {
        return $this->editDefault($employee, new EmployeeWorkScheduleAssignment);
    }

    public function editDefault(Employee $employee, EmployeeWorkScheduleAssignment $scheduleAssignment): View
    {
        return view('admin.scheduling.employee-form', [
            'employee' => $employee,
            'record' => $scheduleAssignment,
            'defaultSchedule' => true,
            'options' => WorkScheduleTemplate::with('office')->orderBy('name')->get(),
        ]);
    }

    public function storeDefault(SaveEmployeeScheduleRequest $request, Employee $employee): RedirectResponse
    {
        return $this->updateDefault($request, $employee, new EmployeeWorkScheduleAssignment);
    }

    public function updateDefault(SaveEmployeeScheduleRequest $request, Employee $employee, EmployeeWorkScheduleAssignment $scheduleAssignment): RedirectResponse
    {
        try {
            $this->schedules->saveDefault($employee, $scheduleAssignment, $request->safe()->except('edit_version'), $request->user()->id, $request->validated('edit_version'));
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.employees.schedules.index', $employee)->with('scheduling_status', 'Jadwal default tersimpan.');
    }

    public function createOverride(Employee $employee): View
    {
        return $this->editOverride($employee, new EmployeeShiftSchedule(['status' => 'scheduled']));
    }

    public function editOverride(Employee $employee, EmployeeShiftSchedule $shiftOverride): View
    {
        return view('admin.scheduling.employee-form', [
            'employee' => $employee,
            'record' => $shiftOverride,
            'defaultSchedule' => false,
            'options' => WorkShift::with('attendanceTime')->orderBy('name')->get(),
            'offices' => Office::orderBy('name')->get(),
        ]);
    }

    public function storeOverride(SaveEmployeeScheduleRequest $request, Employee $employee): RedirectResponse
    {
        return $this->updateOverride($request, $employee, new EmployeeShiftSchedule);
    }

    public function updateOverride(SaveEmployeeScheduleRequest $request, Employee $employee, EmployeeShiftSchedule $shiftOverride): RedirectResponse
    {
        try {
            $this->schedules->saveOverride($employee, $shiftOverride, $request->safe()->except('edit_version'), $request->user()->id, $request->validated('edit_version'));
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.employees.schedules.index', [$employee, 'date' => $request->validated('work_date')])
            ->with('scheduling_status', 'Shift khusus tersimpan.');
    }
}
