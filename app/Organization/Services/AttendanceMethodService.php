<?php

namespace App\Organization\Services;

use App\Organization\Models\AttendanceMethod;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceMethodService
{
    public function paginate(): LengthAwarePaginator
    {
        return AttendanceMethod::orderBy('name')->orderBy('id')->paginate(15);
    }

    public function create(array $data): AttendanceMethod
    {
        $record = new AttendanceMethod;
        $this->save($data, $record);

        return $record;
    }

    public function update(array $data, AttendanceMethod $record, string $editVersion): void
    {
        DB::transaction(function () use ($data, $record, $editVersion): void {
            $current = AttendanceMethod::query()->lockForUpdate()->findOrFail($record->getKey());

            if (! hash_equals($current->editVersion(), $editVersion)) {
                throw ValidationException::withMessages([
                    'edit_version' => 'Metode absen sudah berubah. Buka versi terbaru sebelum menyimpan kembali.',
                ]);
            }

            $this->save($data, $current);
        });
    }

    private function save(array $data, AttendanceMethod $record): void
    {
        try {
            $record->fill($data)->saveOrFail();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => AttendanceMethod::DUPLICATE_MESSAGE]);
        }
    }
}
