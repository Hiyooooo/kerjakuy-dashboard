<?php

namespace App\Scheduling\Services;

use App\Employee\Models\Employee;
use App\Organization\Models\AttendanceLocation;
use App\Organization\Models\AttendanceMethod;
use App\Organization\Models\OutOfLocationReason;
use App\Scheduling\Models\AttendanceRecord;
use App\Scheduling\Models\RegisteredDevice;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AttendanceRecordService
{
    public function __construct(
        private readonly EmployeeScheduleService $schedules,
        private readonly WorkScheduleTemplateService $templates,
    ) {}

    public function checkIn(array $data, int $actor): AttendanceRecord
    {
        return $this->record($data, $actor, null);
    }

    public function checkOut(AttendanceRecord $record, array $data, int $actor): AttendanceRecord
    {
        return $this->record($data, $actor, $record);
    }

    private function record(array $data, int $actor, ?AttendanceRecord $record): AttendanceRecord
    {
        $localTime = CarbonImmutable::parse($data['recorded_at'], 'Asia/Jakarta');
        $recordedAt = $localTime->utc();
        if ($recordedAt->gt(now())) {
            throw ValidationException::withMessages(['recorded_at' => 'Waktu absensi tidak boleh di masa depan.']);
        }

        $path = null;
        try {
            return DB::transaction(function () use ($record, $data, $actor, $localTime, $recordedAt, &$path): AttendanceRecord {
                $event = $record === null ? 'check_in' : 'check_out';
                if ($record === null) {
                    $record = $this->startRecord($data, $localTime);
                } else {
                    $record = AttendanceRecord::query()->lockForUpdate()->findOrFail($record->id);
                    if ($record->check_in_at === null || $record->check_out_at !== null || $recordedAt->lt($record->check_in_at)) {
                        throw ValidationException::withMessages(['recorded_at' => 'Pulang hanya dapat dicatat sekali, setelah absensi masuk.']);
                    }
                    $deadline = $record->check_out_deadline_at ?? $record->scheduled_check_out_at->setTimezone('Asia/Jakarta')->endOfDay();
                    if ($recordedAt->gt($deadline)) {
                        throw ValidationException::withMessages(['recorded_at' => 'Waktu pulang melewati batas absen pulang.']);
                    }
                    if ($record->registered_device_id !== null) {
                        $device = RegisteredDevice::query()->lockForUpdate()->find($record->registered_device_id);
                        $this->validateDevice($device, $record->employee_id, $recordedAt, 'recorded_at');
                    }
                    if ($record->attendance_location_id !== null && $record->location_radius_snapshot === null) {
                        $this->snapshotLocation($record, AttendanceLocation::query()->lockForUpdate()->findOrFail($record->attendance_location_id));
                    }
                }

                $position = $this->position($record, $data);
                $reason = $position['reason'];
                if (isset($data['selfie'])) {
                    $stored = $data['selfie']->store('attendance-selfies', 'local');
                    if ($stored === false) {
                        throw ValidationException::withMessages(['selfie' => 'Foto tidak dapat disimpan. Coba unggah kembali.']);
                    }
                    $path = $stored;
                }

                $record->forceFill([
                    $event.'_at' => $recordedAt,
                    $event.'_by_user_id' => $actor,
                    $event.'_latitude' => $data['latitude'] ?? null,
                    $event.'_longitude' => $data['longitude'] ?? null,
                    $event.'_selfie_path' => $path,
                    ...($event === 'check_in' ? [
                        'distance_meter' => $position['distance'],
                        'out_of_location_reason_id' => $reason?->id,
                        'out_of_location_note' => $data['location_note'] ?? null,
                    ] : [
                        'check_out_distance_meter' => $position['distance'],
                        'check_out_reason_id' => $reason?->id,
                        'check_out_location_note' => $data['location_note'] ?? null,
                    ]),
                ]);
                $record->status = match (true) {
                    $record->check_out_at === null => 'incomplete',
                    $record->out_of_location_reason_id !== null || $record->check_out_reason_id !== null => 'offsite',
                    $record->late_minutes > 0 => 'late',
                    default => 'present',
                };
                $record->saveOrFail();

                return $record;
            });
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    private function startRecord(array $data, CarbonImmutable $localTime): AttendanceRecord
    {
        $day = $data['work_date'];
        $this->templates->lockChanges();
        $device = isset($data['device_id']) ? RegisteredDevice::query()->lockForUpdate()->find($data['device_id']) : null;
        $employee = Employee::query()->where('employee_code', Str::upper($data['employee_code']))->lockForUpdate()->first();
        if ($employee === null || ! $employee->periods()->where('status', 'active')->where('effective_start_date', '<=', $day)
            ->where(fn ($query) => $query->whereNull('effective_end_date')->orWhere('effective_end_date', '>=', $day))->exists()) {
            throw ValidationException::withMessages(['employee_code' => 'Pegawai harus aktif pada tanggal kerja.']);
        }

        if (AttendanceRecord::query()->where('employee_id', $employee->id)->whereDate('work_date', $day)->exists()) {
            throw ValidationException::withMessages(['work_date' => 'Absensi masuk untuk tanggal kerja ini sudah tercatat.']);
        }

        $date = CarbonImmutable::parse($day, 'Asia/Jakarta')->startOfDay();
        $schedule = $this->schedules->effectiveSchedule($employee, $date);
        $shift = $schedule['shift'];
        if ($shift === null) {
            throw ValidationException::withMessages(['work_date' => $schedule['label'].'.']);
        }

        $method = AttendanceMethod::query()->lockForUpdate()->find($data['method_id']);
        if (! $method?->is_active || $method->requires_face_verification) {
            throw ValidationException::withMessages(['method_id' => 'Pilih metode aktif yang tidak mensyaratkan verifikasi wajah.']);
        }

        if ($method->requires_registered_device || isset($data['device_id'])) {
            $this->validateDevice($device, $employee->id, $localTime->utc(), 'device_id');
        }

        $location = isset($data['location_id']) ? AttendanceLocation::query()->lockForUpdate()->find($data['location_id']) : null;
        if (($method->requires_location || isset($data['location_id'])) && ! $location?->is_active) {
            throw ValidationException::withMessages(['location_id' => 'Pilih lokasi absen aktif.']);
        }
        $officeId = $schedule['override_office_id'] ?? $this->schedules->officeOn($employee, $day);
        if ($location?->office_id !== null && (int) $location->office_id !== (int) $officeId) {
            throw ValidationException::withMessages(['location_id' => 'Pilih lokasi global atau lokasi yang sesuai kantor jadwal pegawai.']);
        }

        $time = $shift->attendanceTime;
        $opensAt = $date->addDays($time->check_in_start_day)->setTimeFromTimeString($time->check_in_start);
        $scheduledIn = $date->setTimeFromTimeString($time->expected_check_in);
        $scheduledOut = $date->addDays($time->expected_check_out_day)->setTimeFromTimeString($time->expected_check_out);
        $deadline = $time->check_out_end === null ? $scheduledOut->endOfDay()
            : $date->addDays($time->check_out_end_day)->setTimeFromTimeString($time->check_out_end);
        if ($localTime->lt($opensAt) || $localTime->gt($date->endOfDay()) || $localTime->gte($scheduledOut)) {
            throw ValidationException::withMessages(['recorded_at' => 'Waktu masuk berada di luar jendela absensi tanggal kerja ini.']);
        }
        $lateMinutes = max(0, (int) $scheduledIn->addMinutes($time->late_tolerance_minutes)->diffInMinutes($localTime));

        $record = (new AttendanceRecord)->forceFill([
            'employee_id' => $employee->id,
            'work_date' => $day,
            'work_shift_id' => $shift->id,
            'employee_shift_schedule_id' => $schedule['override_id'] ?? null,
            'attendance_method_id' => $method->id,
            'attendance_location_id' => $location?->id,
            'registered_device_id' => $device?->id,
            'scheduled_check_in_at' => $scheduledIn->utc(),
            'scheduled_check_out_at' => $scheduledOut->utc(),
            'check_out_deadline_at' => $deadline->utc(),
            'late_minutes' => $lateMinutes,
        ]);
        if ($location !== null) {
            $this->snapshotLocation($record, $location);
        }

        return $record;
    }

    private function validateDevice(?RegisteredDevice $device, int $employeeId, CarbonImmutable $time, string $field): void
    {
        if ($device?->status !== 'registered' || ! $device->deviceLocks()->where('employee_id', $employeeId)
            ->where('locked_at', '<=', $time)
            ->where(fn ($query) => $query->whereNull('released_at')->orWhere('released_at', '>', $time))->exists()) {
            throw ValidationException::withMessages([$field => 'Perangkat harus terdaftar dan terkunci ke pegawai pada waktu absensi.']);
        }
    }

    private function snapshotLocation(AttendanceRecord $record, AttendanceLocation $location): void
    {
        $record->forceFill([
            'location_latitude_snapshot' => $location->latitude,
            'location_longitude_snapshot' => $location->longitude,
            'location_radius_snapshot' => $location->radius_meter,
        ]);
    }

    /** @return array{reason: ?OutOfLocationReason, distance: ?float} */
    private function position(AttendanceRecord $record, array $data): array
    {
        $distance = null;
        if ($record->location_radius_snapshot !== null) {
            if (! isset($data['latitude'], $data['longitude'])) {
                throw ValidationException::withMessages(['latitude' => 'Isi lintang dan bujur jika ada lokasi acuan.']);
            }

            $lat = deg2rad((float) $data['latitude']);
            $referenceLat = deg2rad((float) $record->location_latitude_snapshot);
            $deltaLng = deg2rad((float) $data['longitude'] - (float) $record->location_longitude_snapshot);
            $a = sin(($lat - $referenceLat) / 2) ** 2 + cos($lat) * cos($referenceLat) * sin($deltaLng / 2) ** 2;
            $distance = 12742000 * asin(sqrt(min(1, max(0, $a))));
        }

        $outside = $distance !== null && $distance > $record->location_radius_snapshot;
        $reason = isset($data['reason_id']) ? OutOfLocationReason::find($data['reason_id']) : null;
        if ($outside && ! $reason?->is_active) {
            throw ValidationException::withMessages(['reason_id' => 'Pilih alasan aktif karena titik absensi di luar radius.']);
        }
        if (! $outside && (isset($data['reason_id']) || isset($data['location_note']))) {
            throw ValidationException::withMessages(['reason_id' => 'Alasan dan catatan hanya diisi ketika titik absensi di luar radius.']);
        }
        if ($reason?->requires_note && blank($data['location_note'] ?? null)) {
            throw ValidationException::withMessages(['location_note' => 'Isi catatan untuk alasan yang dipilih.']);
        }

        return ['reason' => $reason, 'distance' => $distance];
    }
}
