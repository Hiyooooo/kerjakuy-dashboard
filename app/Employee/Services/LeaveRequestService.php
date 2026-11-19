<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Organization\Models\LeaveCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeaveRequestService
{
    public function adjust(array $data, int $actorId): void
    {
        DB::transaction(function () use ($data, $actorId): void {
            Employee::query()->lockForUpdate()->findOrFail($data['employee_id']);
            $entitlement = DB::table('employee_leave_entitlements')
                ->where('employee_id', $data['employee_id'])
                ->where('leave_category_id', $data['leave_category_id'])
                ->where('entitlement_year', $data['year'])
                ->lockForUpdate()->first(['id']);
            if ($entitlement === null) {
                throw ValidationException::withMessages(['adjustment_days' => 'Jatah untuk kategori dan tahun ini belum tersedia.']);
            }

            $now = now();
            DB::table('leave_balance_adjustments')->insert([
                'employee_leave_entitlement_id' => $entitlement->id,
                'adjustment_days' => $data['adjustment_days'],
                'reason' => $data['reason'],
                'adjusted_by_user_id' => $actorId,
                'adjusted_at' => $now,
                'created_at' => $now,
            ]);
        });
    }

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
                $entitlements = $this->balances($employee->id, (int) $data['leave_category_id'], $years->all());

                $now = now();
                $days = [];
                $requested = [];
                foreach ($data['days'] as $index => $day) {
                    $year = (int) substr($day['date'], 0, 4);
                    if (! $entitlements->has($year)) {
                        throw ValidationException::withMessages(["days.{$index}.date" => "Jatah cuti tahun {$year} untuk pegawai dan kategori ini belum tersedia."]);
                    }

                    $entitlement = $entitlements->get($year);
                    $requested[$year] = ($requested[$year] ?? 0) + (int) round($day['duration'] * 100);
                    if ($requested[$year] > $entitlement->available_cents) {
                        throw ValidationException::withMessages(["days.{$index}.duration" => "Saldo cuti tahun {$year} tidak mencukupi."]);
                    }

                    $days[] = [
                        'employee_leave_entitlement_id' => $entitlement->id,
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

    /** @param array<int, int> $years */
    public function balances(int $employeeId, int $categoryId, array $years): Collection
    {
        $adjustments = DB::table('leave_balance_adjustments')
            ->whereColumn('employee_leave_entitlement_id', 'entitlements.id')->selectRaw('SUM(adjustment_days)');
        $approved = DB::table('leave_request_days as days')
            ->join('requests', 'requests.id', '=', 'days.leave_request_detail_id')
            ->where('requests.status', 'approved')->where('requests.request_type', 'leave')
            ->whereColumn('days.employee_leave_entitlement_id', 'entitlements.id')->selectRaw('SUM(days.duration_days)');

        return DB::table('employee_leave_entitlements as entitlements')
            ->where('employee_id', $employeeId)->where('leave_category_id', $categoryId)->whereIn('entitlement_year', $years)
            ->select(['id', 'entitlement_year', 'granted_days', 'carry_over_days'])
            ->selectSub($adjustments, 'adjusted_days')->selectSub($approved, 'approved_days')
            ->get()->keyBy('entitlement_year')->each(function (object $row): void {
                $row->available_cents = (int) round(($row->granted_days + $row->carry_over_days
                    + ($row->adjusted_days ?? 0) - ($row->approved_days ?? 0)) * 100);
            });
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
