<?php

namespace App\Employee\Services;

use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestApprovalService
{
    public function __construct(
        private readonly ShiftRequestService $shifts,
        private readonly LeaveRequestService $leave,
        private readonly LateRequestService $late,
    ) {}

    public function decide(EmployeeRequest $record, string $decision, User $actor, ?string $note): void
    {
        if ($decision === 'approved' && $record->request_type === 'shift_swap') {
            $this->shifts->approveSwap($record, $actor, $note);

            return;
        }

        DB::transaction(function () use ($record, $decision, $actor, $note): void {
            $record = EmployeeRequest::query()->lockForUpdate()->findOrFail($record->id);
            $record->ensurePending();

            if ($decision === 'approved') {
                $table = match ($record->request_type) {
                    'leave' => 'leave_request_details',
                    'late' => 'late_request_details',
                    'overtime' => 'overtime_request_details',
                };
                $detail = DB::table($table)->where('request_id', $record->id)->first();
                if ($detail === null) {
                    throw ValidationException::withMessages(['approval' => 'Rincian permohonan belum tersedia.']);
                }
                if ($record->request_type === 'leave') {
                    $this->leave->validateApprovalBalance($record, (int) $detail->leave_category_id);
                } elseif ($record->request_type === 'late') {
                    Employee::query()->lockForUpdate()->findOrFail($record->employee_id);
                    $this->late->attendanceForLateRequest($record->employee_id, $detail->late_date, 'approval');
                }
            }

            $record->recordDecision($decision, $actor, $note);
        });
    }
}
