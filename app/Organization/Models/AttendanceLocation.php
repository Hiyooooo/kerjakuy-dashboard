<?php

namespace App\Organization\Models;

use Database\Factories\AttendanceLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['office_id', 'name', 'latitude', 'longitude', 'radius_meter', 'is_active'])]
#[UseFactory(AttendanceLocationFactory::class)]
class AttendanceLocation extends Model
{
    /** @use HasFactory<AttendanceLocationFactory> */
    use HasFactory;

    public const int MAX_RADIUS_METER = 1000;

    /** @return BelongsTo<Office, $this> */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'radius_meter' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
