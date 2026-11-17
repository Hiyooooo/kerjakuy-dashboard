<?php

namespace App\Employee\Models;

use App\Organization\Models\Office;
use App\Organization\Models\Position;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['office_id', 'position_id', 'effective_start_date', 'effective_end_date', 'change_type', 'notes'])]
class EmployeeJobAssignment extends Model
{
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    protected function casts(): array
    {
        return ['effective_start_date' => 'immutable_date', 'effective_end_date' => 'immutable_date'];
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }
}
