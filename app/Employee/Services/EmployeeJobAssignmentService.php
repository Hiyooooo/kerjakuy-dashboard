<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeJobAssignment;
use App\Organization\Models\Office;
use App\Organization\Models\Position;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeJobAssignmentService
{
    public function save(Employee $employee, EmployeeJobAssignment $assignment, array $data, ?string $editVersion): void
    {
        DB::transaction(function () use ($employee, $assignment, $data, $editVersion): void {
            Employee::query()->lockForUpdate()->findOrFail($employee->getKey());

            if ($assignment->exists) {
                $assignment = $employee->assignments()->findOrFail($assignment->getKey());
                if (! hash_equals($assignment->editVersion(), $editVersion ?? '')) {
                    throw ValidationException::withMessages([
                        'edit_version' => 'Penempatan sudah berubah. Buka versi terbaru sebelum menyimpan.',
                    ]);
                }
            }

            $end = $data['effective_end_date'] ?? null;
            $overlap = $employee->assignments()
                ->when($assignment->exists, fn ($query) => $query->whereKeyNot($assignment))
                ->when($end !== null, fn ($query) => $query->whereDate('effective_start_date', '<=', $end))
                ->where(fn ($query) => $query->whereNull('effective_end_date')
                    ->orWhereDate('effective_end_date', '>=', $data['effective_start_date']))
                ->exists();

            if ($overlap) {
                throw ValidationException::withMessages([
                    'effective_start_date' => 'Tanggal penempatan bertumpang tindih. Akhiri penempatan lama sebelum menambah penempatan baru.',
                ]);
            }

            foreach (['office_id' => Office::class, 'position_id' => Position::class] as $field => $model) {
                if ($model::query()->lockForUpdate()->find($data[$field]) === null) {
                    throw ValidationException::withMessages([
                        $field => $field === 'office_id' ? 'Kantor sudah tidak tersedia. Pilih kantor lain.' : 'Jabatan sudah tidak tersedia. Pilih jabatan lain.',
                    ]);
                }
            }

            $assignment->fill([...$data, 'effective_end_date' => $end])->saveOrFail();
        });
    }
}
