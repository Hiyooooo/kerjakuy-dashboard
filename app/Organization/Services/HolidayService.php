<?php

namespace App\Organization\Services;

use App\Organization\Models\Holiday;
use App\Organization\Models\Office;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HolidayService
{
    public function paginate(int $year, ?int $officeId): LengthAwarePaginator
    {
        return Holiday::with('office:id,code,name')
            ->whereYear('holiday_date', sprintf('%04d', $year))
            ->when($officeId !== null, fn (Builder $query): Builder => $query->where(
                fn (Builder $scope): Builder => $scope->whereNull('office_id')->orWhere('office_id', $officeId)
            ))
            ->orderByDesc('holiday_date')
            ->orderByRaw("CASE official_type WHEN 'national_holiday' THEN 0 WHEN 'joint_leave' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);
    }

    public function create(array $data): Holiday
    {
        $record = new Holiday;
        $this->update($data, $record);

        return $record;
    }

    public function update(array $data, Holiday $record): void
    {
        try {
            DB::transaction(function () use ($data, $record): void {
                if ($record->exists) {
                    $record = Holiday::query()->lockForUpdate()->findOrFail($record->getKey());
                    abort_if($record->official_type !== null, 403);
                }

                $record->fill($data);

                if ($record->office_id !== null && $record->isDirty('office_id')
                    && ! Office::whereKey($record->office_id)->where('is_active', true)->lockForUpdate()->first()) {
                    throw ValidationException::withMessages(['office_id' => 'Pilih kantor yang masih aktif.']);
                }

                $record->save();
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['name' => Holiday::DUPLICATE_MESSAGE]);
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '23503'
                && ! ($exception->getCode() === '23000' && str_contains($exception->getMessage(), 'FOREIGN KEY constraint failed'))) {
                throw $exception;
            }

            throw ValidationException::withMessages(['office_id' => 'Kantor yang dipilih sudah tidak tersedia.']);
        }
    }

    public function delete(Holiday $record): void
    {
        abort_if(Holiday::whereKey($record->getKey())->whereNull('official_type')->delete() === 0, 403);
    }
}
