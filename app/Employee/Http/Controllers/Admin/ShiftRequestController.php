<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\StoreShiftRequest;
use App\Employee\Models\EmployeeRequest;
use App\Employee\Services\ShiftRequestService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftRequestController extends Controller
{
    public function index(Request $request): View
    {
        $overtime = $request->routeIs('admin.overtime-requests.*');
        $table = $overtime ? 'overtime_request_details' : 'shift_swap_request_details';
        $date = $overtime ? 'overtime_date' : 'requester_work_date';
        $requests = EmployeeRequest::with('employee:id,full_name,employee_code')
            ->join($table.' as detail', 'detail.request_id', '=', 'requests.id')
            ->where('requests.request_type', $overtime ? 'overtime' : 'shift_swap')
            ->latest('requests.id')->paginate(20, ['requests.*', 'detail.'.$date.' as request_date']);

        return view('admin.employees.shift-requests.index', compact('requests', 'overtime'));
    }

    public function create(Request $request): View
    {
        return view('admin.employees.shift-requests.form', ['overtime' => $request->routeIs('admin.overtime-requests.*')]);
    }

    public function store(StoreShiftRequest $request, ShiftRequestService $service): RedirectResponse
    {
        $overtime = $request->routeIs('admin.overtime-requests.*');
        $record = $overtime ? $service->submitOvertime($request->validated()) : $service->submitSwap($request->validated());

        return to_route($overtime ? 'admin.overtime-requests.show' : 'admin.shift-swap-requests.show', $record)
            ->with('shift_request_status', 'Permohonan tersimpan.');
    }

    public function show(Request $request, EmployeeRequest $record): View
    {
        $overtime = $request->routeIs('admin.overtime-requests.*');
        abort_unless($record->request_type === ($overtime ? 'overtime' : 'shift_swap'), 404);
        $query = DB::table($overtime ? 'overtime_request_details as detail' : 'shift_swap_request_details as detail')
            ->where('detail.request_id', $record->id)->select('detail.*');
        if (! $overtime) {
            $query->join('employees as target', 'target.id', '=', 'detail.target_employee_id')
                ->join('work_shifts as original', 'original.id', '=', 'detail.requester_work_shift_id')
                ->join('work_shifts as partner', 'partner.id', '=', 'detail.target_work_shift_id')
                ->addSelect(['target.full_name as target_name', 'target.employee_code as target_code', 'original.name as original_shift', 'partner.name as partner_shift']);
        }
        $detail = $query->first();
        abort_if($detail === null, 404);

        return view('admin.employees.shift-requests.show', compact('record', 'detail', 'overtime'));
    }

    public function approve(Request $request, EmployeeRequest $record, ShiftRequestService $service): RedirectResponse
    {
        $data = $request->validate(['reviewer_note' => ['nullable', 'string', 'max:2000']]);
        try {
            $service->approveSwap($record, $request->user()->id, $data['reviewer_note'] ?? null);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('admin.shift-swap-requests.show', $record));
        }

        return to_route('admin.shift-swap-requests.show', $record)->with('shift_request_status', 'Tukar shift disetujui. Jadwal kedua pegawai sudah diperbarui.');
    }
}
