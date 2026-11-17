<?php

namespace App\Scheduling\Services;

use App\Organization\Models\Office;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkScheduleTemplateService
{
    public function lockChanges(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Serialize edits to templates and their shared shift and time references.
            DB::statement('LOCK TABLE work_schedule_templates IN SHARE ROW EXCLUSIVE MODE');
        }
    }

    public function validateActiveTemplates(Builder $templates, string $field): void
    {
        foreach ($templates->where('is_active', true)->with('days.shift.attendanceTime')->lazyById() as $template) {
            $days = $template->days->pluck('work_shift_id', 'weekday_number')->all();
            $shifts = new Collection($template->days->pluck('shift')->filter()->keyBy('id')->all());

            try {
                $this->validatePattern($days, $shifts, true);
            } catch (ValidationException $exception) {
                throw ValidationException::withMessages([
                    $field => 'Perubahan membuat template aktif “'.$template->name.'” tidak valid. '.$exception->validator->errors()->first(),
                ]);
            }
        }
    }

    public function save(WorkScheduleTemplate $template, array $data, array $days, ?string $editVersion): void
    {
        DB::transaction(function () use ($template, $data, $days, $editVersion): void {
            $this->lockChanges();

            if ($template->exists) {
                $template = WorkScheduleTemplate::query()->lockForUpdate()->findOrFail($template->getKey());

                if (! hash_equals($template->editVersion(), $editVersion ?? '')) {
                    throw ValidationException::withMessages(['edit_version' => 'Template sudah berubah. Buka versi terbaru sebelum menyimpan kembali.']);
                }
            }

            $officeId = $data['office_id'] ?? null;
            if ($officeId !== null && Office::query()->lockForUpdate()->find($officeId) === null) {
                throw ValidationException::withMessages(['office_id' => 'Kantor sudah tidak tersedia.']);
            }

            $shiftIds = array_filter(array_map('intval', $days));
            $shifts = WorkShift::with('attendanceTime')->whereKey(array_unique($shiftIds))->get()->keyBy('id');
            $this->validatePattern($days, $shifts, (bool) $data['is_active']);

            $template->fill($data)->saveOrFail();
            foreach ($days as $weekday => $shiftId) {
                $shiftId = (int) $shiftId;
                $template->days()->updateOrCreate(['weekday_number' => $weekday], [
                    'work_shift_id' => $shiftId ?: null,
                    'is_working_day' => $shiftId !== 0,
                ]);
            }
        });
    }

    /** @param Collection<int, WorkShift> $shifts */
    private function validatePattern(array $days, Collection $shifts, bool $active): void
    {
        foreach ($days as $weekday => $shiftId) {
            if ((int) $shiftId === 0) {
                continue;
            }

            $shift = $shifts->get($shiftId);
            if ($shift === null) {
                throw ValidationException::withMessages(["days.$weekday" => 'Shift ini sudah tidak tersedia.']);
            }

            if ($active && (! $shift->is_active || ! $shift->attendanceTime->is_active)) {
                $day = WorkScheduleTemplate::WEEKDAYS[$weekday];
                throw ValidationException::withMessages(["days.$weekday" => "Pilih shift dan Waktu Absen aktif untuk $day."]);
            }

            $nextDay = $weekday % 7 + 1;
            $next = $shifts->get($days[$nextDay] ?? 0);
            $time = $shift->attendanceTime;
            if ($next && $time->expected_check_out_day === 1
                && substr($time->expected_check_out, 0, 5) > substr($next->attendanceTime->expected_check_in, 0, 5)) {
                $day = WorkScheduleTemplate::WEEKDAYS[$weekday];
                $nextLabel = WorkScheduleTemplate::WEEKDAYS[$nextDay];
                throw ValidationException::withMessages(["days.$nextDay" => "Shift $nextLabel dimulai sebelum shift $day selesai."]);
            }
        }
    }
}
