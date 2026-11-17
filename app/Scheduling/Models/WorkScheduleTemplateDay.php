<?php

namespace App\Scheduling\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['weekday_number', 'work_shift_id', 'is_working_day'])]
class WorkScheduleTemplateDay extends Model
{
    public function shift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'work_shift_id');
    }

    protected function casts(): array
    {
        return ['weekday_number' => 'integer', 'is_working_day' => 'boolean'];
    }
}
