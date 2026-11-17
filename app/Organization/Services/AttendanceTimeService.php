<?php

namespace App\Organization\Services;

use App\Organization\Models\AttendanceTime;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Services\WorkScheduleTemplateService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceTimeService
{
    public function __construct(private readonly WorkScheduleTemplateService $templates) {}

    public function paginate(): LengthAwarePaginator
    {
        return AttendanceTime::orderBy('name')->orderBy('id')->paginate(15);
    }

    public function create(array $data): AttendanceTime
    {
        $record = new AttendanceTime;
        $this->update($data, $record);

        return $record;
    }

    public function update(array $data, AttendanceTime $record): void
    {
        try {
            DB::transaction(function () use ($record, $data): void {
                if ($record->exists) {
                    $this->templates->lockChanges();
                }

                $record->fill($data)->saveOrFail();

                if ($record->wasChanged(['is_active', 'expected_check_in', 'expected_check_out', 'expected_check_out_day'])) {
                    $this->templates->validateActiveTemplates(
                        WorkScheduleTemplate::whereHas('days.shift', fn ($shifts) => $shifts->where('attendance_time_id', $record->id)),
                        $record->is_active ? 'expected_check_out' : 'is_active',
                    );
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'name' => 'Nama waktu absen ini sudah dipakai.',
            ]);
        }
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
