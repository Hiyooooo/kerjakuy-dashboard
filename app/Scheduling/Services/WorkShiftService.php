<?php

namespace App\Scheduling\Services;

use App\Organization\Models\AttendanceTime;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkShiftService
{
    public function __construct(private readonly WorkScheduleTemplateService $templates) {}

    public function save(WorkShift $shift, array $data, ?string $editVersion): void
    {
        try {
            DB::transaction(function () use ($shift, $data, $editVersion): void {
                $this->templates->lockChanges();

                if ($shift->exists) {
                    $shift = WorkShift::query()->lockForUpdate()->findOrFail($shift->getKey());
                    if (! hash_equals($shift->editVersion(), $editVersion ?? '')) {
                        throw ValidationException::withMessages(['edit_version' => 'Shift sudah berubah. Buka versi terbaru sebelum menyimpan kembali.']);
                    }
                }

                if (AttendanceTime::query()->lockForUpdate()->find($data['attendance_time_id']) === null) {
                    throw ValidationException::withMessages(['attendance_time_id' => 'Waktu absen sudah tidak tersedia.']);
                }

                $shift->fill($data)->saveOrFail();
                if ($shift->wasChanged(['attendance_time_id', 'is_active'])) {
                    $this->templates->validateActiveTemplates(
                        WorkScheduleTemplate::whereHas('days', fn ($days) => $days->where('work_shift_id', $shift->id)),
                        $shift->is_active ? 'attendance_time_id' : 'is_active',
                    );
                }
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'Kode shift sudah digunakan.']);
        }
    }
}
