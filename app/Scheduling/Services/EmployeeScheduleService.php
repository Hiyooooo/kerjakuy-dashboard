<?php

namespace App\Scheduling\Services;

use App\Employee\Models\Employee;
use App\Organization\Models\Office;
use App\Scheduling\Models\EmployeeShiftSchedule;
use App\Scheduling\Models\EmployeeWorkScheduleAssignment;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeScheduleService
{
    public function __construct(private readonly WorkScheduleTemplateService $templates) {}

    public function saveDefault(Employee $employee, EmployeeWorkScheduleAssignment $record, array $data, int $actor, ?string $version): void
    {
        DB::transaction(function () use ($employee, $record, $data, $actor, $version): void {
            $this->lockSchedule($employee);
            if ($record->exists) {
                $record = $employee->scheduleAssignments()->findOrFail($record->id);
                $this->checkVersion($record->editVersion(), $version);
            } else {
                $record = $employee->scheduleAssignments()->make();
                $record->assigned_by_user_id = $actor;
            }

            $start = $data['effective_start_date'];
            $end = $data['effective_end_date'] ?? null;
            $template = WorkScheduleTemplate::find($data['work_schedule_template_id']);
            if (! $template?->is_active) {
                throw ValidationException::withMessages(['work_schedule_template_id' => 'Pilih template jadwal aktif.']);
            }

            if ($template->office_id !== null && ! $this->officeCoversPeriod($employee, $template->office_id, $start, $end)) {
                throw ValidationException::withMessages(['work_schedule_template_id' => 'Penempatan di kantor template harus mencakup seluruh periode jadwal. Sesuaikan tanggal atau pilih template global.']);
            }

            $conflict = $employee->scheduleAssignments()
                ->when($record->exists, fn ($query) => $query->whereKeyNot($record))
                ->when($end !== null, fn ($query) => $query->whereDate('effective_start_date', '<=', $end))
                ->where(fn ($query) => $query->whereNull('effective_end_date')->orWhereDate('effective_end_date', '>=', $start))
                ->orderBy('effective_start_date')
                ->first(['effective_start_date', 'effective_end_date']);

            if ($conflict !== null) {
                $conflictStart = $conflict->effective_start_date->locale('id')->translatedFormat('j F Y');
                $conflictEnd = $conflict->effective_end_date?->locale('id')->translatedFormat('j F Y');
                $period = $conflictEnd === null ? "mulai {$conflictStart} tanpa tanggal akhir" : "{$conflictStart} sampai {$conflictEnd}";

                throw ValidationException::withMessages([
                    'effective_start_date' => "Periode bertumpang tindih dengan jadwal default {$period}. Sesuaikan tanggal mulai atau tanggal akhir.",
                ]);
            }

            $record->fill([...$data, 'effective_end_date' => $end])->saveOrFail();
        });
    }

    public function saveOverride(Employee $employee, EmployeeShiftSchedule $record, array $data, int $actor, ?string $version): void
    {
        DB::transaction(function () use ($employee, $record, $data, $actor, $version): void {
            $this->lockSchedule($employee);
            if ($record->exists) {
                $record = $employee->shiftOverrides()->findOrFail($record->id);
                $this->checkVersion($record->editVersion(), $version);
            } else {
                $record = $employee->shiftOverrides()->make();
                $record->created_by_user_id = $actor;
            }

            $shift = WorkShift::with('attendanceTime')->find($data['work_shift_id']);
            if ($shift === null || ($data['status'] === 'scheduled' && (! $shift->is_active || ! $shift->attendanceTime->is_active))) {
                throw ValidationException::withMessages(['work_shift_id' => 'Pilih shift dengan waktu absen aktif.']);
            }

            $officeId = $data['office_id'] ?? null;
            if ($officeId !== null && Office::query()->lockForUpdate()->find($officeId) === null) {
                throw ValidationException::withMessages(['office_id' => 'Kantor sudah tidak tersedia.']);
            }

            if ($employee->shiftOverrides()->whereDate('work_date', $data['work_date'])
                ->when($record->exists, fn ($query) => $query->whereKeyNot($record))->exists()) {
                throw ValidationException::withMessages(['work_date' => 'Sudah ada shift khusus pada tanggal ini. Edit entri yang ada.']);
            }

            $previousDate = $record->work_date;
            $record->fill([...$data, 'office_id' => $officeId])->saveOrFail();

            $this->checkAdjacentShifts($employee, $record->work_date);
            if ($previousDate !== null && ! $previousDate->isSameDay($record->work_date)) {
                $this->checkAdjacentShifts($employee, $previousDate);
            }
        });
    }

    /** @return array{label: string, shift: ?WorkShift, override_id?: int, override_office_id?: ?int} */
    public function effectiveSchedule(Employee $employee, CarbonImmutable $date): array
    {
        $day = $date->toDateString();
        $override = $employee->shiftOverrides()->whereDate('work_date', $day)->where('status', 'scheduled')->with('shift.attendanceTime')->first();
        if ($override !== null) {
            return [...$this->resolveShift('Shift khusus', $override->shift, $date), 'override_id' => $override->id, 'override_office_id' => $override->office_id];
        }

        $assignment = $this->effectiveOn($employee->scheduleAssignments()->getQuery(), $day)
            ->with('template')->first();
        if ($assignment === null) {
            return ['label' => 'Belum ada jadwal default pada tanggal ini', 'shift' => null];
        }

        $template = $assignment->template;
        if ($template->office_id !== null && (int) $template->office_id !== (int) $this->officeOn($employee, $day)) {
            return ['label' => 'Template tidak berlaku untuk kantor pegawai pada tanggal ini', 'shift' => null];
        }

        if (! $template->is_active && $this->isCurrentOrFuture($date)) {
            return ['label' => 'Template jadwal default sudah nonaktif', 'shift' => null];
        }

        $templateDay = $template->days()->where('weekday_number', $date->dayOfWeekIso)->with('shift.attendanceTime')->first();

        return match (true) {
            $templateDay === null => ['label' => 'Hari ini belum diatur pada template', 'shift' => null],
            ! $templateDay->is_working_day => ['label' => 'Libur mingguan', 'shift' => null],
            default => $this->resolveShift('Jadwal default', $templateDay->shift, $date),
        };
    }

    /** @return array{label: string, shift: ?WorkShift} */
    private function resolveShift(string $label, ?WorkShift $shift, CarbonImmutable $date): array
    {
        if ($shift === null || $shift->attendanceTime === null) {
            return ['label' => $label.' · Shift atau waktu absen tidak tersedia', 'shift' => null];
        }

        if ($this->isCurrentOrFuture($date) && (! $shift->is_active || ! $shift->attendanceTime->is_active)) {
            return ['label' => $label.' · Shift atau waktu absen sudah nonaktif', 'shift' => null];
        }

        return ['label' => $label, 'shift' => $shift];
    }

    private function isCurrentOrFuture(CarbonImmutable $date): bool
    {
        return $date->toDateString() >= CarbonImmutable::today('Asia/Jakarta')->toDateString();
    }

    private function checkAdjacentShifts(Employee $employee, CarbonImmutable $date): void
    {
        $current = $this->effectiveSchedule($employee, $date)['shift']?->attendanceTime;
        $previousDate = $date->subDay();
        $nextDate = $date->addDay();
        $previous = $this->effectiveSchedule($employee, $previousDate)['shift']?->attendanceTime;
        $next = $this->effectiveSchedule($employee, $nextDate)['shift']?->attendanceTime;

        $conflictStart = match (true) {
            $previous?->expected_check_out_day === 1 && $current !== null
                && substr($previous->expected_check_out, 0, 5) > substr($current->expected_check_in, 0, 5) => $previousDate,
            $current?->expected_check_out_day === 1 && $next !== null
                && substr($current->expected_check_out, 0, 5) > substr($next->expected_check_in, 0, 5) => $date,
            default => null,
        };

        if ($conflictStart !== null) {
            throw ValidationException::withMessages(['work_date' => 'Shift tanggal '.$conflictStart->toDateString().' bertumpang tindih dengan shift tanggal '.$conflictStart->addDay()->toDateString().'.']);
        }
    }

    private function officeCoversPeriod(Employee $employee, int $officeId, string $start, ?string $end): bool
    {
        $placements = $employee->assignments()->where('office_id', $officeId)
            ->where(fn ($query) => $query->whereNull('effective_end_date')->orWhereDate('effective_end_date', '>=', $start))
            ->orderBy('effective_start_date')->get();

        $coveredThrough = $start;
        foreach ($placements as $placement) {
            if ($placement->effective_start_date->toDateString() > $coveredThrough) {
                break;
            }

            if ($placement->effective_end_date === null || ($end !== null && $placement->effective_end_date->toDateString() >= $end)) {
                return true;
            }

            $coveredThrough = $placement->effective_end_date->addDay()->toDateString();
        }

        return false;
    }

    public function officeOn(Employee $employee, string $date): ?int
    {
        return $this->effectiveOn($employee->assignments()->getQuery(), $date)->value('office_id');
    }

    private function effectiveOn(Builder $query, string $date): Builder
    {
        return $query->whereDate('effective_start_date', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_end_date')->orWhereDate('effective_end_date', '>=', $date));
    }

    private function lockSchedule(Employee $employee): void
    {
        $this->templates->lockChanges();
        Employee::query()->lockForUpdate()->findOrFail($employee->getKey());
    }

    private function checkVersion(string $current, ?string $submitted): void
    {
        if (! hash_equals($current, $submitted ?? '')) {
            throw ValidationException::withMessages(['edit_version' => 'Jadwal sudah berubah. Buka versi terbaru sebelum menyimpan.']);
        }
    }
}
