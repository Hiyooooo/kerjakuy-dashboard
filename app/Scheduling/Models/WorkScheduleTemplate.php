<?php

namespace App\Scheduling\Models;

use App\Organization\Models\Office;
use Database\Factories\WorkScheduleTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['office_id', 'name', 'description', 'is_active'])]
#[UseFactory(WorkScheduleTemplateFactory::class)]
class WorkScheduleTemplate extends Model
{
    /** @use HasFactory<WorkScheduleTemplateFactory> */
    use HasFactory;

    public const array WEEKDAYS = [1 => 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkScheduleTemplateDay::class)->orderBy('weekday_number');
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable()), $this->days->pluck('work_shift_id', 'weekday_number')->all()]));
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
