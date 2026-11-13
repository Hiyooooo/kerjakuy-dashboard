<?php

namespace App\Services;

use App\Models\Position;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class PositionService
{
    public function paginate(): LengthAwarePaginator
    {
        return Position::orderBy('name')->orderBy('id')->paginate(15);
    }

    public function create(array $data): Position
    {
        return Position::create($data);
    }

    public function update(array $data, Position $record): void
    {
        $record->update($data);
    }

    public function delete(Position $record): void
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
                'delete' => 'Jabatan masih dipakai. Ubah statusnya menjadi Nonaktif.',
            ]);
        }
    }
}
