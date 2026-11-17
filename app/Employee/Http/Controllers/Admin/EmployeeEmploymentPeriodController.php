<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\SaveEmploymentPeriodRequest;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeEmploymentPeriod;
use App\Employee\Services\EmployeeEmploymentPeriodService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class EmployeeEmploymentPeriodController extends Controller
{
    public function __construct(private readonly EmployeeEmploymentPeriodService $periods) {}

    public function index(Employee $employee): View
    {
        return view('admin.employees.periods', [
            'employee' => $employee,
            'periods' => $employee->periods()->orderByDesc('effective_start_date')->paginate(15),
            'today' => today('Asia/Jakarta')->toDateString(),
        ]);
    }

    public function create(Employee $employee): View
    {
        return $this->edit($employee, new EmployeeEmploymentPeriod(['status' => 'active']));
    }

    public function edit(Employee $employee, EmployeeEmploymentPeriod $period): View
    {
        return view('admin.employees.period-form', compact('employee', 'period'));
    }

    public function store(SaveEmploymentPeriodRequest $request, Employee $employee): RedirectResponse
    {
        return $this->update($request, $employee, $employee->periods()->make());
    }

    public function update(SaveEmploymentPeriodRequest $request, Employee $employee, EmployeeEmploymentPeriod $period): RedirectResponse
    {
        $data = $request->safe()->except('edit_version');

        try {
            $this->periods->save($employee, $period, $data, $request->validated('edit_version'));
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.employees.periods.index', $employee)->with('employee_status', 'Periode kepegawaian tersimpan.');
    }
}
