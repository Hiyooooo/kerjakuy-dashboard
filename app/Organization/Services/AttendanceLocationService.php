<?php

namespace App\Organization\Services;

use App\Organization\Models\AttendanceLocation;
use App\Organization\Models\Office;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceLocationService
{
    public function save(AttendanceLocation $location, array $data, ?string $editVersion): void
    {
        DB::transaction(function () use ($location, $data, $editVersion): void {
            $officeId = $data['office_id'] ?? null;

            if ($officeId !== null && ! Office::query()->lockForUpdate()->find($officeId)) {
                throw ValidationException::withMessages(['office_id' => 'Kantor tidak ditemukan. Pilih kantor lain.']);
            }

            if ($location->exists) {
                $location = AttendanceLocation::query()->lockForUpdate()->findOrFail($location->getKey());

                if ($editVersion === null || ! hash_equals($location->editVersion(), $editVersion)) {
                    throw ValidationException::withMessages([
                        'edit_version' => 'Lokasi absen sudah berubah. Buka versi terbaru sebelum menyimpan kembali.',
                    ]);
                }
            }

            $location->fill([...$data, 'office_id' => $officeId])->saveOrFail();
        });
    }
}
