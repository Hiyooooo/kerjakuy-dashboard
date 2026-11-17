<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function save(Employee $employee, array $data, ?string $editVersion): void
    {
        try {
            DB::transaction(function () use ($employee, $data, $editVersion): void {
                if ($employee->exists) {
                    $employee = Employee::query()->lockForUpdate()->findOrFail($employee->getKey());

                    if ($editVersion === null || ! hash_equals($employee->editVersion(), $editVersion)) {
                        throw ValidationException::withMessages([
                            'edit_version' => 'Data pegawai sudah berubah. Buka versi terbaru sebelum menyimpan kembali.',
                        ]);
                    }
                }

                $employee->fill($data)->saveOrFail();
            });
        } catch (UniqueConstraintViolationException $exception) {
            $message = $exception->errorInfo[2] ?? '';
            foreach (['employee_code' => 'Kode pegawai', 'email' => 'Email'] as $field => $label) {
                if (str_contains($message, "employees_{$field}_unique") || str_contains($message, "employees.{$field}")) {
                    throw ValidationException::withMessages([$field => "$label sudah digunakan."]);
                }
            }

            throw $exception;
        }
    }
}
