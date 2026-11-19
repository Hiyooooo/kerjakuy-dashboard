<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Scheduling\Models\AttendanceRecord;
use App\Scheduling\Models\EmployeeShiftSchedule;
use App\Scheduling\Models\WorkShift;
use App\Scheduling\Services\EmployeeScheduleService;
use App\Scheduling\Services\WorkScheduleTemplateService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ShiftRequestService
{
    public function __construct(
        private readonly EmployeeScheduleService $schedules,
        private readonly WorkScheduleTemplateService $templates,
    ) {}

    public function submitOvertime(array $data): EmployeeRequest
    {
        return DB::transaction(function () use ($data): EmployeeRequest {
            $this->templates->lockChanges();
            $employee = $this->findEmployee($data['employee_code'], 'employee_code');
            $date = CarbonImmutable::parse($data['overtime_date'], 'Asia/Jakarta')->startOfDay();
            $start = $date->setTimeFromTimeString($data['start_time']);
            $end = $date->setTimeFromTimeString($data['end_time']);
            $nextDay = (bool) ($data['ends_next_day'] ?? false);
            if ($nextDay !== $end->lt($start) || $end->eq($start)) {
                throw ValidationException::withMessages(['end_time' => 'Jika jam selesai lebih awal dari jam mulai, pilih Selesai hari berikutnya. Untuk hari yang sama, jam selesai harus lebih akhir.']);
            }
            if ($nextDay) {
                $end = $end->addDay();
            }
            $minutes = (int) $start->diffInMinutes($end);
            $existing = DB::table('overtime_request_details as detail')
                ->join('requests', 'requests.id', '=', 'detail.request_id')
                ->where('requests.employee_id', $employee->id)->where('requests.request_type', 'overtime')
                ->whereIn('requests.status', ['pending', 'approved'])
                ->whereBetween('detail.overtime_date', [$date->subDay()->toDateString(), $end->toDateString()])
                ->get(['detail.overtime_date', 'detail.start_time', 'detail.total_minutes']);
            foreach ($existing as $entry) {
                $existingStart = CarbonImmutable::parse($entry->overtime_date.' '.$entry->start_time, 'Asia/Jakarta');
                $existingEnd = $existingStart->addMinutes((int) $entry->total_minutes);
                if ($start->lt($existingEnd) && $end->gt($existingStart)) {
                    throw ValidationException::withMessages(['start_time' => 'Waktu lembur bertumpang tindih dengan permohonan yang menunggu atau sudah disetujui.']);
                }
            }

            $request = $this->createRequest($employee, 'overtime');
            DB::table('overtime_request_details')->insert([
                'request_id' => $request->id,
                'employee_shift_schedule_id' => $this->schedules->effectiveSchedule($employee, $date)['override_id'] ?? null,
                'overtime_date' => $data['overtime_date'],
                'start_time' => $data['start_time'],
                'end_time' => $data['end_time'],
                'total_minutes' => $minutes,
                'reason' => $data['reason'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $request;
        });
    }

    public function submitSwap(array $data): EmployeeRequest
    {
        return DB::transaction(function () use ($data): EmployeeRequest {
            $this->templates->lockChanges();
            $employee = $this->findEmployee($data['employee_code'], 'employee_code');
            $target = $this->findEmployee($data['target_employee_code'], 'target_employee_code');
            if ($employee->is($target)) {
                throw ValidationException::withMessages(['target_employee_code' => 'Pilih pegawai lain untuk tukar shift.']);
            }
            $requester = $this->swapSchedule($employee, $data['requester_work_date'], 'requester_work_date');
            $partner = $this->swapSchedule($target, $data['target_work_date'], 'target_work_date');
            $duplicate = DB::table('shift_swap_request_details as detail')->join('requests', 'requests.id', '=', 'detail.request_id')
                ->where('requests.request_type', 'shift_swap')->where('requests.status', 'pending')
                ->whereNotNull('detail.requester_schedule_snapshot')->whereNotNull('detail.target_schedule_snapshot')
                ->where(fn ($query) => $query
                    ->where([
                        'requests.employee_id' => $employee->id, 'detail.target_employee_id' => $target->id,
                        'detail.requester_work_date' => $data['requester_work_date'], 'detail.target_work_date' => $data['target_work_date'],
                    ])
                    ->orWhere([
                        'requests.employee_id' => $target->id, 'detail.target_employee_id' => $employee->id,
                        'detail.requester_work_date' => $data['target_work_date'], 'detail.target_work_date' => $data['requester_work_date'],
                    ]))
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['target_work_date' => 'Pertukaran ini sudah memiliki permohonan yang menunggu keputusan.']);
            }
            $request = $this->createRequest($employee, 'shift_swap');
            DB::table('shift_swap_request_details')->insert([
                'request_id' => $request->id,
                'target_employee_id' => $target->id,
                'requester_work_date' => $data['requester_work_date'],
                'target_work_date' => $data['target_work_date'],
                'requester_work_shift_id' => $requester['shift']->id,
                'target_work_shift_id' => $partner['shift']->id,
                'requester_schedule_snapshot' => json_encode($requester['snapshot'], JSON_THROW_ON_ERROR),
                'target_schedule_snapshot' => json_encode($partner['snapshot'], JSON_THROW_ON_ERROR),
                'reason' => $data['reason'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $request;
        });
    }

    public function approveSwap(EmployeeRequest $request, int $actor, ?string $note): void
    {
        DB::transaction(function () use ($request, $actor, $note): void {
            $this->templates->lockChanges();
            $request = EmployeeRequest::query()->where('request_type', 'shift_swap')->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== 'pending') {
                throw ValidationException::withMessages(['approval' => 'Permohonan ini sudah diproses.']);
            }
            $detail = DB::table('shift_swap_request_details')->where('request_id', $request->id)->first();
            abort_if($detail === null, 404);
            if ($detail->requester_schedule_snapshot === null || $detail->target_schedule_snapshot === null) {
                throw ValidationException::withMessages(['approval' => 'Jam efektif saat pengajuan lama tidak tercatat. Buat permohonan baru.']);
            }
            $employees = Employee::query()->whereKey([$request->employee_id, $detail->target_employee_id])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $employee = $employees[$request->employee_id];
            $target = $employees[$detail->target_employee_id];
            $requester = $this->swapSchedule($employee, $detail->requester_work_date, 'approval');
            $partner = $this->swapSchedule($target, $detail->target_work_date, 'approval');
            if ((int) $requester['shift']->id !== (int) $detail->requester_work_shift_id
                || (int) $partner['shift']->id !== (int) $detail->target_work_shift_id
                || $requester['snapshot'] != json_decode($detail->requester_schedule_snapshot, true, flags: JSON_THROW_ON_ERROR)
                || $partner['snapshot'] != json_decode($detail->target_schedule_snapshot, true, flags: JSON_THROW_ON_ERROR)) {
                throw ValidationException::withMessages(['approval' => 'Shift efektif sudah berubah sejak pengajuan. Buat permohonan baru.']);
            }
            $this->validateCheckInWindow($detail->requester_work_date, $partner['shift'], 'approval');
            $this->validateCheckInWindow($detail->target_work_date, $requester['shift'], 'approval');

            $this->applyOverride($employee, $detail->requester_work_date, $partner['shift']->id, $request->request_number, $actor);
            $this->applyOverride($target, $detail->target_work_date, $requester['shift']->id, $request->request_number, $actor);
            $request->forceFill([
                'status' => 'approved',
                'reviewed_by_user_id' => $actor,
                'reviewer_note' => $note,
                'reviewed_at' => now(),
            ])->saveOrFail();
        });
    }

    private function applyOverride(Employee $employee, string $date, int $shiftId, string $number, int $actor): void
    {
        $override = $employee->shiftOverrides()->whereDate('work_date', $date)->first() ?? new EmployeeShiftSchedule;
        $this->schedules->saveOverride($employee, $override, [
            'work_date' => $date,
            'work_shift_id' => $shiftId,
            'office_id' => $override->status === 'scheduled' ? $override->office_id : null,
            'status' => 'scheduled',
            'reason' => 'Tukar shift '.$number,
        ], $actor, $override->exists ? $override->editVersion() : null);
    }

    private function swapSchedule(Employee $employee, string $day, string $field): array
    {
        $date = CarbonImmutable::parse($day, 'Asia/Jakarta');
        if ($date->lt(today('Asia/Jakarta')) || AttendanceRecord::where('employee_id', $employee->id)->whereDate('work_date', $day)->exists()) {
            throw ValidationException::withMessages([$field => 'Tanggal kerja sudah lewat atau sudah memiliki catatan absensi.']);
        }

        $schedule = $this->schedules->effectiveSchedule($employee, $date);
        if ($schedule['shift'] === null) {
            throw ValidationException::withMessages([$field => 'Pegawai harus memiliki shift efektif pada tanggal kerja tersebut.']);
        }
        $shift = $schedule['shift'];
        $this->validateCheckInWindow($day, $shift, $field);
        $schedule['snapshot'] = [
            ...$shift->attendanceTime->only([
                'check_in_start', 'check_in_start_day', 'expected_check_in', 'expected_check_out',
                'expected_check_out_day', 'check_out_end', 'check_out_end_day', 'late_tolerance_minutes',
            ]),
            'office_id' => $schedule['override_office_id'] ?? $this->schedules->officeOn($employee, $day),
        ];

        return $schedule;
    }

    private function validateCheckInWindow(string $day, WorkShift $shift, string $field): void
    {
        $time = $shift->attendanceTime;
        $opensAt = CarbonImmutable::parse($day, 'Asia/Jakarta')->startOfDay()
            ->addDays($time->check_in_start_day)->setTimeFromTimeString($time->check_in_start);
        if ($opensAt->lte(now())) {
            throw ValidationException::withMessages([$field => 'Pertukaran harus diproses sebelum jendela absen masuk dibuka.']);
        }
    }

    private function createRequest(Employee $employee, string $type): EmployeeRequest
    {
        return $employee->requests()->create([
            'request_number' => 'REQ-'.Str::ulid(),
            'request_type' => $type,
            'submitted_at' => now(),
        ]);
    }

    private function findEmployee(string $code, string $field): Employee
    {
        $employee = Employee::where('employee_code', Str::upper($code))->lockForUpdate()->first();
        if ($employee === null) {
            throw ValidationException::withMessages([$field => 'Kode pegawai tidak ditemukan.']);
        }

        return $employee;
    }
}
