<?php

namespace App\Scheduling\Models;

use App\Employee\Models\Employee;
use App\Organization\Models\AttendanceLocation;
use App\Organization\Models\AttendanceMethod;
use App\Organization\Models\OutOfLocationReason;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Hidden(['check_in_selfie_path', 'check_out_selfie_path'])]
class AttendanceRecord extends Model
{
    use HasFactory;

    public function faceRecognitionLogs(): HasMany
    {
        return $this->hasMany(FaceRecognitionLog::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'work_shift_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(AttendanceMethod::class, 'attendance_method_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'attendance_location_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(RegisteredDevice::class, 'registered_device_id');
    }

    public function checkInReason(): BelongsTo
    {
        return $this->belongsTo(OutOfLocationReason::class, 'out_of_location_reason_id');
    }

    public function checkOutReason(): BelongsTo
    {
        return $this->belongsTo(OutOfLocationReason::class, 'check_out_reason_id');
    }

    protected function casts(): array
    {
        return [
            'work_date' => 'immutable_date',
            'scheduled_check_in_at' => 'immutable_datetime',
            'scheduled_check_out_at' => 'immutable_datetime',
            'check_out_deadline_at' => 'immutable_datetime',
            'check_in_at' => 'immutable_datetime',
            'check_out_at' => 'immutable_datetime',
        ];
    }
}
