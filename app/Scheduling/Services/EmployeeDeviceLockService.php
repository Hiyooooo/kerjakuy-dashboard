<?php

namespace App\Scheduling\Services;

use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Scheduling\Models\EmployeeDeviceLock;
use App\Scheduling\Models\RegisteredDevice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeDeviceLockService
{
    public function lock(RegisteredDevice $device, string $employeeCode, User $actor): void
    {
        DB::transaction(function () use ($device, $employeeCode, $actor): void {
            $device = RegisteredDevice::query()->lockForUpdate()->findOrFail($device->id);
            if ($device->status !== 'registered') {
                throw ValidationException::withMessages(['employee_code' => 'Perangkat yang diblokir tidak bisa dikunci.']);
            }

            $employee = Employee::query()->where('employee_code', $employeeCode)->lockForUpdate()->first();
            if ($employee === null) {
                throw ValidationException::withMessages(['employee_code' => 'Kode pegawai tidak ditemukan.']);
            }

            $today = today('Asia/Jakarta')->toDateString();
            $isActive = $employee->periods()->where('status', 'active')
                ->whereDate('effective_start_date', '<=', $today)
                ->where(fn ($query) => $query->whereNull('effective_end_date')->orWhereDate('effective_end_date', '>=', $today))
                ->exists();
            if (! $isActive) {
                throw ValidationException::withMessages(['employee_code' => 'Perangkat hanya bisa dikunci untuk pegawai yang sedang aktif.']);
            }

            if ($device->deviceLocks()->whereNull('released_at')->exists()) {
                throw ValidationException::withMessages(['employee_code' => 'Perangkat sudah terkunci. Lepaskan penguncian aktif terlebih dahulu.']);
            }

            if (EmployeeDeviceLock::query()->where('employee_id', $employee->id)->whereNull('released_at')->exists()) {
                throw ValidationException::withMessages(['employee_code' => 'Pegawai sudah memiliki perangkat terkunci. Lepaskan perangkat sebelumnya terlebih dahulu.']);
            }

            $device->deviceLocks()->create([
                'employee_id' => $employee->id,
                'locked_by_user_id' => $actor->id,
                'locked_by_name' => $actor->name,
                'locked_at' => now(),
            ]);
        });
    }

    public function release(EmployeeDeviceLock $lock, User $actor): bool
    {
        return EmployeeDeviceLock::query()->whereKey($lock->id)->whereNull('released_at')->update([
            'released_by_user_id' => $actor->id,
            'released_by_name' => $actor->name,
            'released_at' => now(),
        ]) === 1;
    }
}
