<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\SaveJobAssignmentRequest;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeJobAssignment;
use App\Employee\Services\EmployeeJobAssignmentService;
use App\Http\Controllers\Controller;
use App\Organization\Models\Office;
use App\Organization\Models\Position;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class EmployeeJobAssignmentController extends Controller
{
    public function __construct(private readonly EmployeeJobAssignmentService $assignments) {}

    public function index(Employee $employee): View
    {
        return view('admin.employees.assignments', [
            'employee' => $employee,
            'assignments' => $employee->assignments()->with(['office', 'position'])->orderByDesc('effective_start_date')->paginate(15),
            'today' => today('Asia/Jakarta')->toDateString(),
        ]);
    }

    public function create(Employee $employee): View
    {
        return $this->edit($employee, new EmployeeJobAssignment);
    }

    public function edit(Employee $employee, EmployeeJobAssignment $assignment): View
    {
        return view('admin.employees.assignment-form', [
            'employee' => $employee,
            'assignment' => $assignment,
            'offices' => Office::orderBy('name')->get(['id', 'name', 'code', 'is_active']),
            'positions' => Position::orderBy('name')->get(['id', 'name', 'code', 'is_active']),
        ]);
    }

    public function store(SaveJobAssignmentRequest $request, Employee $employee): RedirectResponse
    {
        return $this->update($request, $employee, $employee->assignments()->make());
    }

    public function update(SaveJobAssignmentRequest $request, Employee $employee, EmployeeJobAssignment $assignment): RedirectResponse
    {
        $data = $request->safe()->except('edit_version');

        try {
            $this->assignments->save($employee, $assignment, $data, $request->validated('edit_version'));
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.employees.assignments.index', $employee)->with('employee_status', 'Riwayat penempatan tersimpan.');
    }
}
