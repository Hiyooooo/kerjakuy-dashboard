<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\StoreLeaveRequest;
use App\Employee\Models\EmployeeRequest;
use App\Employee\Services\LeaveRequestService;
use App\Http\Controllers\Controller;
use App\Organization\Models\LeaveCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveRequestController extends Controller
{
    public function index(): View
    {
        return view('admin.employees.leave-requests.index', [
            'requests' => EmployeeRequest::with('employee')->where('request_type', 'leave')
                ->whereIn('id', DB::table('leave_request_details')->select('request_id'))
                ->latest('id')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return $this->form(false);
    }

    public function createEntitlement(): View
    {
        return $this->form(true);
    }

    public function store(StoreLeaveRequest $request, LeaveRequestService $service): RedirectResponse
    {
        $leave = $service->submit($request->validated());

        return to_route('admin.leave-requests.show', $leave)->with('leave_status', 'Permohonan cuti tercatat.');
    }

    public function storeEntitlement(StoreLeaveRequest $request, LeaveRequestService $service): RedirectResponse
    {
        $service->grant($request->validated());

        return to_route('admin.leave-requests.entitlements.create')->with('leave_status', 'Jatah cuti tersimpan.');
    }

    public function show(EmployeeRequest $leaveRequest): View
    {
        abort_unless($leaveRequest->request_type === 'leave', 404);
        $detail = DB::table('leave_request_details')->join('leave_categories', 'leave_categories.id', '=', 'leave_category_id')
            ->select('leave_request_details.*', 'leave_categories.name as category')->where('request_id', $leaveRequest->id)->first();
        abort_if($detail === null, 404);
        $days = DB::table('leave_request_days')->join('employee_leave_entitlements', 'employee_leave_entitlements.id', '=', 'employee_leave_entitlement_id')
            ->where('leave_request_detail_id', $leaveRequest->id)->orderBy('leave_date')
            ->get(['leave_date', 'duration_days', 'entitlement_year']);

        return view('admin.employees.leave-requests.show', compact('leaveRequest', 'detail', 'days'));
    }

    public function attachment(EmployeeRequest $leaveRequest): StreamedResponse
    {
        abort_unless($leaveRequest->request_type === 'leave', 404);
        $path = DB::table('leave_request_details')->where('request_id', $leaveRequest->id)->value('attachment_path');
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->download($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function form(bool $entitlement): View
    {
        return view('admin.employees.leave-requests.form', [
            'entitlement' => $entitlement,
            'categories' => LeaveCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
