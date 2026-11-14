<?php

namespace App\Organization\Services;

use App\Organization\Models\AttendanceTime;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AttendanceTimeService
{
    public function paginate(): LengthAwarePaginator
    {
        return AttendanceTime::orderBy('name')->orderBy('id')->paginate(15);
    }

    public function create(array $data): AttendanceTime
    {
        return AttendanceTime::create($data);
    }

    public function update(array $data, AttendanceTime $record): void
    {
        $record->update($data);
    }

    public function delete(AttendanceTime $record): void
    {
        try {
            $record->deleteOrFail();
        } catch (QueryException $exception) {
            $foreignKeyViolation = in_array($exception->getCode(), ['23503', '23001'], true)
                || ($exception->getCode() === '23000' && str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed'));

            if (! $foreignKeyViolation) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'delete' => 'Waktu absen masih dipakai. Ubah statusnya menjadi Nonaktif.',
            ]);
        }
    }
}
