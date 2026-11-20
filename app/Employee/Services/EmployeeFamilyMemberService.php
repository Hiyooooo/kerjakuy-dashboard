<?php

namespace App\Employee\Services;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeFamilyMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeFamilyMemberService
{
    public function save(Employee $employee, EmployeeFamilyMember $familyMember, array $data, ?string $editVersion): void
    {
        DB::transaction(function () use ($employee, $familyMember, $data, $editVersion): void {
            if ($familyMember->exists) {
                $familyMember = $employee->familyMembers()->lockForUpdate()->findOrFail($familyMember->getKey());
                if (! hash_equals($familyMember->editVersion(), $editVersion ?? '')) {
                    throw ValidationException::withMessages(['edit_version' => 'Data keluarga sudah berubah. Buka versi terbaru sebelum menyimpan.']);
                }
            }

            $previousStart = $familyMember->tax_dependent_start_date?->toDateString();
            $previousEnd = $familyMember->tax_dependent_end_date?->toDateString();
            $newPeriod = (bool) ($data['start_new_tax_period'] ?? false);
            unset($data['start_new_tax_period']);
            $familyMember->fill([
                ...$data,
                'tax_dependent_start_date' => $data['tax_dependent_start_date'] ?? null,
                'tax_dependent_end_date' => $data['tax_dependent_end_date'] ?? null,
            ]);

            $birth = $familyMember->birth_date?->toDateString();
            $start = $familyMember->tax_dependent_start_date?->toDateString();
            if ($birth !== null && $start !== null && $start < $birth) {
                throw ValidationException::withMessages(['tax_dependent_start_date' => 'Tanggal mulai tanggungan tidak boleh sebelum tanggal lahir.']);
            }

            if ($familyMember->exists) {
                if (! $newPeriod && $previousEnd !== null && $start !== null && $start > $previousEnd) {
                    throw ValidationException::withMessages(['start_new_tax_period' => 'Pilih Mulai periode baru untuk menyimpan periode sebelumnya dalam riwayat.']);
                }
                $periods = DB::table('employee_family_tax_periods')->where('employee_family_member_id', $familyMember->id);
                if ($birth !== null && (clone $periods)->where('start_date', '<', $birth)->exists()) {
                    throw ValidationException::withMessages(['birth_date' => 'Tanggal lahir tidak boleh setelah awal periode dalam riwayat tanggungan.']);
                }
                if ($start !== null && (clone $periods)->where('end_date', '>=', $start)->exists()) {
                    throw ValidationException::withMessages(['tax_dependent_start_date' => 'Tanggal mulai harus setelah periode dalam riwayat tanggungan.']);
                }
                if ($newPeriod) {
                    if ($previousStart === null || $previousEnd === null || $start === null || $start <= $previousEnd) {
                        throw ValidationException::withMessages(['tax_dependent_start_date' => 'Tutup periode sebelumnya, lalu pilih tanggal mulai setelah tanggal akhirnya.']);
                    }
                    if ($birth !== null && $birth > $previousStart) {
                        throw ValidationException::withMessages(['birth_date' => 'Tanggal lahir tidak boleh setelah awal periode sebelumnya.']);
                    }
                    $periods->insert([
                        'employee_family_member_id' => $familyMember->id,
                        'start_date' => $previousStart,
                        'end_date' => $previousEnd,
                    ]);
                }
            }

            $familyMember->saveOrFail();
        });
    }
}
