<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeIdentityService
{
    public function save(Employee $employee, string $gender, #[\SensitiveParameter] ?string $nik, string $editVersion): void
    {
        try {
            DB::transaction(function () use ($employee, $gender, $nik, $editVersion): void {
                $employee = Employee::query()->lockForUpdate()->findOrFail($employee->getKey());
                $identity = $employee->identityDetail()->firstOrNew();

                if (! hash_equals($identity->exists ? $identity->editVersion() : 'new', $editVersion)) {
                    throw ValidationException::withMessages([
                        'edit_version' => 'Identitas sudah berubah. Buka versi terbaru sebelum menyimpan kembali.',
                    ]);
                }

                if ($nik !== null) {
                    $identity->setNationalId($nik);
                } elseif (! $identity->exists) {
                    throw ValidationException::withMessages(['national_id' => 'Masukkan NIK dengan 16 digit angka.']);
                }

                $identity->gender = $gender;
                $identity->saveOrFail();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['national_id' => 'NIK sudah digunakan.']);
        }
    }
}
