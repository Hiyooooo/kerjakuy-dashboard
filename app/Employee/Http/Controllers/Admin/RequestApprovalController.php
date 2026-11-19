<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Models\EmployeeRequest;
use App\Employee\Services\RequestApprovalService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RequestApprovalController extends Controller
{
    private const array TYPES = [
        'leave' => ['Cuti', 'admin.leave-requests.show'],
        'late' => ['Terlambat', 'admin.late-requests.show'],
        'overtime' => ['Lembur', 'admin.overtime-requests.show'],
        'shift_swap' => ['Tukar shift', 'admin.shift-swap-requests.show'],
    ];

    private const array STATUSES = [
        'pending' => 'Menunggu persetujuan',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'cancelled' => 'Dibatalkan',
    ];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(self::TYPES))],
            'status' => ['nullable', Rule::in(['all', ...array_keys(self::STATUSES)])],
        ]);
        $status = $filters['status'] ?? 'pending';
        $records = EmployeeRequest::with(['employee:id,full_name,employee_code', 'reviewer:id,name'])
            ->select('requests.*')->selectRaw("
                CASE request_type
                    WHEN 'leave' THEN EXISTS (SELECT 1 FROM leave_request_details WHERE request_id = requests.id)
                    WHEN 'late' THEN EXISTS (SELECT 1 FROM late_request_details WHERE request_id = requests.id)
                    WHEN 'overtime' THEN EXISTS (SELECT 1 FROM overtime_request_details WHERE request_id = requests.id)
                    WHEN 'shift_swap' THEN EXISTS (SELECT 1 FROM shift_swap_request_details WHERE request_id = requests.id)
                    ELSE FALSE
                END AS has_detail
            ")->withCasts(['has_detail' => 'boolean'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('request_type', $type))
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('submitted_at')->orderByDesc('id')->paginate(20)->appends($filters);

        return view('admin.employees.request-approvals.index', [
            'records' => $records,
            'type' => $filters['type'] ?? '',
            'status' => $status,
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
        ]);
    }

    public function decide(Request $request, EmployeeRequest $record, RequestApprovalService $service): RedirectResponse
    {
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected', 'cancelled'])],
            'reviewer_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $service->decide($record, $data['decision'], $request->user(), $data['reviewer_note'] ?? null);

        return to_route('admin.request-approvals.index')
            ->with('approval_status', 'Permohonan '.strtolower(self::STATUSES[$data['decision']]).'.');
    }
}
