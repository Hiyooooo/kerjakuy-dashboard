<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\SaveFamilyMemberRequest;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeFamilyMember;
use App\Employee\Services\EmployeeFamilyMemberService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeFamilyMemberController extends Controller
{
    public function __construct(private readonly EmployeeFamilyMemberService $familyMembers) {}

    public function index(Employee $employee): View
    {
        return view('admin.employees.family-members', [
            'employee' => $employee,
            'familyMembers' => $employee->familyMembers()->orderBy('full_name')->orderBy('id')->paginate(15),
            'today' => today('Asia/Jakarta')->toDateString(),
        ]);
    }

    public function create(Employee $employee): View
    {
        return $this->edit($employee, new EmployeeFamilyMember);
    }

    public function edit(Employee $employee, EmployeeFamilyMember $familyMember): View
    {
        $pastPeriods = DB::table('employee_family_tax_periods')
            ->where('employee_family_member_id', $familyMember->getKey())->orderByDesc('start_date')->get();

        return view('admin.employees.family-member-form', compact('employee', 'familyMember', 'pastPeriods'));
    }

    public function store(SaveFamilyMemberRequest $request, Employee $employee): RedirectResponse
    {
        return $this->update($request, $employee, $employee->familyMembers()->make());
    }

    public function update(SaveFamilyMemberRequest $request, Employee $employee, EmployeeFamilyMember $familyMember): RedirectResponse
    {
        try {
            $this->familyMembers->save($employee, $familyMember, $request->safe()->except('edit_version'), $request->validated('edit_version'));
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.employees.family-members.index', $employee)->with('employee_status', 'Data keluarga tersimpan.');
    }
}
