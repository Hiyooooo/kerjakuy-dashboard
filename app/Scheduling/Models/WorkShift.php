<?php

namespace App\Scheduling\Models;

use App\Organization\Models\AttendanceTime;
use Database\Factories\WorkShiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_time_id', 'code', 'name', 'is_active'])]
#[UseFactory(WorkShiftFactory::class)]
class WorkShift extends Model
{
    /** @use HasFactory<WorkShiftFactory> */
    use HasFactory;

    public function attendanceTime(): BelongsTo
    {
        return $this->belongsTo(AttendanceTime::class);
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
