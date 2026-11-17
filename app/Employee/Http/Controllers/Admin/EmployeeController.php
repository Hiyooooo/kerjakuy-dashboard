<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\SaveEmployeeRequest;
use App\Employee\Models\Employee;
use App\Employee\Services\EmployeeService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employees) {}

    public function index(Request $request): View
    {
        $query = $request->query();
        $search = is_string($query['search'] ?? null) ? Str::substr(trim($query['search']), 0, 150) : '';
        $employees = Employee::orderBy('full_name')->orderBy('id');

        if ($search !== '') {
            $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $employees->where(fn ($query) => $query
                ->whereRaw("LOWER(full_name) LIKE LOWER(?) ESCAPE '!'", [$pattern])
                ->orWhereRaw("LOWER(employee_code) LIKE LOWER(?) ESCAPE '!'", [$pattern]));
        }

        return view('admin.employees.index', [
            'employees' => $employees->paginate(15, ['id', 'employee_code', 'full_name'])->appends(['search' => $search]),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return $this->edit(new Employee);
    }

    public function store(SaveEmployeeRequest $request): RedirectResponse
    {
        return $this->update($request, new Employee);
    }

    public function edit(Employee $employee): View
    {
        return view('admin.employees.form', ['employee' => $employee]);
    }

    public function update(SaveEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        try {
            $this->employees->save($employee, $request->safe()->except('edit_version'), $request->validated('edit_version'));
        } catch (ValidationException $exception) {
            throw $request->forEmployeeForm($exception);
        }

        return to_route('admin.employees.index')->with('employee_status', 'Data pegawai tersimpan.');
    }
}
