<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Organization\Models\LeaveCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeaveRequestService
{
    public function grant(array $data): void
    {
        DB::transaction(function () use ($data): void {
            $employee = $this->lockEmployeeAndCategory($data);
            $key = [
                'employee_id' => $employee->id,
                'leave_category_id' => $data['leave_category_id'],
                'entitlement_year' => $data['entitlement_year'],
            ];
            $entitlements = DB::table('employee_leave_entitlements');
            if ($entitlements->where($key)->exists()) {
                throw ValidationException::withMessages(['entitlement_year' => 'Jatah untuk pegawai, kategori, dan tahun ini sudah ada.']);
            }

            $entitlements->insert([
                ...$key,
                'granted_days' => $data['granted_days'],
                'carry_over_days' => $data['carry_over_days'] ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function submit(array $data): EmployeeRequest
    {
        $path = null;
        try {
            return DB::transaction(function () use ($data, &$path): EmployeeRequest {
                $employee = $this->lockEmployeeAndCategory($data);
                $years = collect($data['days'])->pluck('date')->map(fn (string $date): int => (int) substr($date, 0, 4))->unique();
                $entitlements = DB::table('employee_leave_entitlements')
                    ->where('employee_id', $employee->id)->where('leave_category_id', $data['leave_category_id'])
                    ->whereIn('entitlement_year', $years)->pluck('id', 'entitlement_year');

                $now = now();
                $days = [];
                foreach ($data['days'] as $index => $day) {
                    $year = (int) substr($day['date'], 0, 4);
                    if (! $entitlements->has($year)) {
                        throw ValidationException::withMessages(["days.{$index}.date" => "Jatah cuti tahun {$year} untuk pegawai dan kategori ini belum tersedia."]);
                    }

                    $days[] = [
                        'employee_leave_entitlement_id' => $entitlements->get($year),
                        'leave_date' => $day['date'],
                        'duration_days' => $day['duration'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if (isset($data['attachment'])) {
                    $stored = $data['attachment']->store('leave-request-attachments', 'local');
                    if ($stored === false) {
                        throw ValidationException::withMessages(['attachment' => 'Lampiran gagal disimpan.']);
                    }
                    $path = $stored;
                }

                $request = $employee->requests()->create([
                    'request_number' => 'REQ-'.Str::ulid(),
                    'request_type' => 'leave',
                    'submitted_at' => $now,
                ]);
                DB::table('leave_request_details')->insert([
                    'request_id' => $request->id,
                    'leave_category_id' => $data['leave_category_id'],
                    'reason' => $data['reason'] ?? null,
                    'attachment_path' => $path,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                foreach (array_chunk($days, 100) as $batch) {
                    DB::table('leave_request_days')->insert(array_map(fn (array $day): array => [
                        ...$day, 'leave_request_detail_id' => $request->id,
                    ], $batch));
                }

                return $request;
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }
            throw $exception;
        }
    }

    private function lockEmployeeAndCategory(array $data): Employee
    {
        $employee = Employee::query()->where('employee_code', Str::upper($data['employee_code']))->lockForUpdate()->first();
        if ($employee === null) {
            throw ValidationException::withMessages(['employee_code' => 'Kode pegawai tidak ditemukan.']);
        }

        if (! LeaveCategory::query()->lockForUpdate()->find($data['leave_category_id'])?->is_active) {
            throw ValidationException::withMessages(['leave_category_id' => 'Pilih kategori cuti yang aktif.']);
        }

        return $employee;
    }
}
