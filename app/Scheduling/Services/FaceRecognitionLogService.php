<?php

namespace App\Scheduling\Services;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeFaceProfile;
use App\Organization\Models\FaceTolerancePolicy;
use App\Scheduling\Models\AttendanceRecord;
use App\Scheduling\Models\FaceRecognitionLog;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use LogicException;
use Throwable;

class FaceRecognitionLogService
{
    /** Record a result from a trusted verifier. Persist failed attempts outside the attendance transaction. */
    public function record(
        Employee $employee,
        string $type,
        CarbonImmutable $capturedAt,
        ?float $score = null,
        ?EmployeeFaceProfile $profile = null,
        ?UploadedFile $photo = null,
        ?string $failureReason = null,
        ?AttendanceRecord $attendance = null,
    ): FaceRecognitionLog {
        Validator::make(compact('type', 'score', 'photo', 'failureReason'), [
            'type' => ['required', 'in:check_in,check_out'],
            'score' => ['nullable', 'numeric', 'between:0,100', 'decimal:0,2'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'failureReason' => ['nullable', 'string', 'max:255'],
        ])->validate();

        if (($profile !== null && $profile->employee_id !== $employee->id)
            || ($attendance !== null && $attendance->employee_id !== $employee->id)) {
            throw ValidationException::withMessages(['employee' => 'Profil wajah dan absensi harus milik pegawai yang sama.']);
        }

        $day = $capturedAt->setTimezone('Asia/Jakarta')->toDateString();
        $threshold = FaceTolerancePolicy::query()->whereDate('effective_start_date', '<=', $day)
            ->where(fn ($query) => $query->whereNull('effective_end_date')->orWhereDate('effective_end_date', '>=', $day))
            ->value('minimum_match_score');

        $reason = $failureReason ?? match (true) {
            $profile === null => 'Profil referensi wajah tidak tersedia.',
            $capturedAt->lt($profile->uploaded_at) || ($profile->deactivated_at !== null && $capturedAt->gte($profile->deactivated_at)) => 'Profil referensi wajah tidak berlaku pada waktu pengambilan.',
            $threshold === null => 'Kebijakan toleransi wajah tidak tersedia.',
            $score === null => 'Skor kecocokan tidak tersedia.',
            $score < $threshold => 'Skor di bawah batas minimum.',
            default => null,
        };

        if ($reason !== null && DB::transactionLevel() > 0) {
            throw new LogicException('Record failed face verification before opening the attendance transaction.');
        }

        try {
            $path = $photo?->store('face-verification-photos', 'local') ?: null;
        } catch (FilesystemException $exception) {
            report($exception);
            $path = null;
        }

        try {
            $log = (new FaceRecognitionLog)->forceFill([
                'employee_id' => $employee->id,
                'attendance_record_id' => $attendance?->id,
                'employee_face_profile_id' => $profile?->id,
                'verification_type' => $type,
                'captured_photo_path' => $path,
                'match_score' => $score,
                'minimum_match_score' => $threshold,
                'status' => $reason === null ? 'passed' : 'failed',
                'failure_reason' => $reason,
                'captured_at' => $capturedAt->utc(),
            ]);
            $log->saveOrFail();

            return $log;
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }
}
