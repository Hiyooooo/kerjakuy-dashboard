<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeEmploymentPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeEmploymentPeriodService
{
    public function save(Employee $employee, EmployeeEmploymentPeriod $period, array $data, ?string $editVersion): void
    {
        DB::transaction(function () use ($employee, $period, $data, $editVersion): void {
            Employee::query()->lockForUpdate()->findOrFail($employee->getKey());

            if ($period->exists) {
                $period = $employee->periods()->findOrFail($period->getKey());
                if (! hash_equals($period->editVersion(), $editVersion ?? '')) {
                    throw ValidationException::withMessages([
                        'edit_version' => 'Periode sudah berubah. Buka versi terbaru sebelum menyimpan.',
                    ]);
                }
            }

            $end = $data['effective_end_date'] ?? null;
            $overlap = $employee->periods()
                ->when($period->exists, fn ($query) => $query->whereKeyNot($period))
                ->when($end !== null, fn ($query) => $query->whereDate('effective_start_date', '<=', $end))
                ->where(fn ($query) => $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $data['effective_start_date']))
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'effective_start_date' => 'Periode bertumpang tindih. Akhiri periode lama sebelum memulai periode baru.',
                ]);
            }

            $period->fill([...$data, 'effective_end_date' => $end])->saveOrFail();
        });
    }
}
