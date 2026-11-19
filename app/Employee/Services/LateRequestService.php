<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Scheduling\Models\AttendanceRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LateRequestService
{
    public function submit(array $data): EmployeeRequest
    {
        $path = null;
        try {
            return DB::transaction(function () use ($data, &$path): EmployeeRequest {
                $employee = Employee::where('employee_code', Str::upper($data['employee_code']))->lockForUpdate()->first();
                if ($employee === null) {
                    throw ValidationException::withMessages(['employee_code' => 'Kode pegawai tidak ditemukan.']);
                }

                $attendance = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereDate('work_date', $data['late_date'])->first();
                if ($attendance?->check_in_at !== null && $attendance->late_minutes <= 0) {
                    throw ValidationException::withMessages(['late_date' => 'Catatan absensi pada tanggal ini tidak menunjukkan keterlambatan setelah toleransi.']);
                }

                $alreadyRequested = $employee->requests()->where('request_type', 'late')
                    ->whereIn('status', ['pending', 'approved'])
                    ->whereIn('requests.id', DB::table('late_request_details')->whereDate('late_date', $data['late_date'])->select('request_id'))
                    ->exists();
                if ($alreadyRequested) {
                    throw ValidationException::withMessages(['late_date' => 'Sudah ada permohonan terlambat yang menunggu persetujuan atau disetujui pada tanggal ini.']);
                }

                if (isset($data['attachment'])) {
                    $stored = $data['attachment']->store('late-request-attachments', 'local');
                    if ($stored === false) {
                        throw ValidationException::withMessages(['attachment' => 'Lampiran gagal disimpan.']);
                    }
                    $path = $stored;
                }

                $now = now();
                $request = $employee->requests()->create([
                    'request_number' => 'REQ-'.Str::ulid(),
                    'request_type' => 'late',
                    'submitted_at' => $now,
                ]);
                DB::table('late_request_details')->insert([
                    'request_id' => $request->id,
                    'attendance_record_id' => $attendance?->id,
                    'late_date' => $data['late_date'],
                    'expected_check_in' => $attendance?->scheduled_check_in_at?->setTimezone('Asia/Jakarta')->format('H:i:s') ?? ($data['expected_check_in'] ?? null),
                    'actual_check_in' => $attendance?->check_in_at?->setTimezone('Asia/Jakarta')->format('H:i:s') ?? ($data['actual_check_in'] ?? null),
                    'reason' => $data['reason'],
                    'attachment_path' => $path,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                return $request;
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
