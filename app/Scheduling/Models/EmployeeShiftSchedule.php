<?php

namespace App\Scheduling\Models;

use App\Organization\Models\Office;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['work_shift_id', 'office_id', 'work_date', 'status', 'reason'])]
class EmployeeShiftSchedule extends Model
{
    public function shift(): BelongsTo
    {
        return $this->belongsTo(WorkShift::class, 'work_shift_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'office_id');
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }

    protected function casts(): array
    {
        return ['work_date' => 'immutable_date'];
    }
}
