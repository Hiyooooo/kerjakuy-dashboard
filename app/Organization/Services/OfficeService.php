<?php

namespace App\Organization\Services;

use App\Organization\Models\Office;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OfficeService
{
    public function paginate(): LengthAwarePaginator
    {
        return Office::orderBy('name')->orderBy('id')->paginate(15);
    }

    public function create(array $data): Office
    {
        return Office::create($data);
    }

    public function update(array $data, Office $record): void
    {
        $record->update($data);
    }

    public function delete(Office $record): void
    {
        try {
            $record->delete();
        } catch (QueryException $exception) {
            $foreignKeyViolation = $exception->getCode() === '23503'
                || ($exception->getCode() === '23000' && str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed'));

            if (! $foreignKeyViolation) {
                throw $exception;
            }

            throw ValidationException::withMessages([
                'delete' => 'Kantor masih dipakai. Ubah statusnya menjadi Nonaktif.',
            ]);
        }
    }
}
