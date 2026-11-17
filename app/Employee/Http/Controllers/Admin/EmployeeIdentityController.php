<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\SaveEmployeeIdentityRequest;
use App\Employee\Models\Employee;
use App\Employee\Services\EmployeeIdentityService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class EmployeeIdentityController extends Controller
{
    public function __construct(private readonly EmployeeIdentityService $identities) {}

    public function edit(Employee $employee): View
    {
        return view('admin.employees.identity', ['employee' => $employee, 'identity' => $employee->identityDetail]);
    }

    public function update(SaveEmployeeIdentityRequest $request, Employee $employee): RedirectResponse
    {
        try {
            $this->identities->save(
                $employee,
                $request->validated('gender'),
                $request->validated('national_id'),
                $request->validated('edit_version'),
            );
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.employees.identity.edit', $employee)->with('employee_status', 'Identitas pegawai tersimpan.');
    }
}
