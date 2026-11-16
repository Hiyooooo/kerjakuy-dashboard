<?php

namespace App\Organization\Services;

use App\Organization\Models\OutOfLocationReason;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OutOfLocationReasonService
{
    public function paginate(): LengthAwarePaginator
    {
        return OutOfLocationReason::orderBy('name')->orderBy('id')->paginate(15);
    }

    public function create(array $data): OutOfLocationReason
    {
        $record = new OutOfLocationReason;
        $this->update($data, $record);

        return $record;
    }

    public function update(array $data, OutOfLocationReason $record): void
    {
        try {
            $record->fill($data)->saveOrFail();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => OutOfLocationReason::DUPLICATE_MESSAGE]);
        }
    }

    public function delete(OutOfLocationReason $record): void
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
                'delete' => 'Alasan masih dipakai. Ubah statusnya menjadi Nonaktif.',
            ]);
        }
    }
}
