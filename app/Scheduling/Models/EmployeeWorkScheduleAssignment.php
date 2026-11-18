<?php

namespace App\Scheduling\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['work_schedule_template_id', 'effective_start_date', 'effective_end_date'])]
class EmployeeWorkScheduleAssignment extends Model
{
    public function template(): BelongsTo
    {
        return $this->belongsTo(WorkScheduleTemplate::class, 'work_schedule_template_id');
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }

    protected function casts(): array
    {
        return ['effective_start_date' => 'immutable_date', 'effective_end_date' => 'immutable_date'];
    }
}
