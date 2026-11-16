<?php

namespace App\Organization\Models;

use Database\Factories\OutOfLocationReasonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'requires_note', 'is_active'])]
#[UseFactory(OutOfLocationReasonFactory::class)]
class OutOfLocationReason extends Model
{
    /** @use HasFactory<OutOfLocationReasonFactory> */
    use HasFactory;

    public const string DUPLICATE_MESSAGE = 'Nama alasan ini sudah dipakai.';

    /** @return Builder<static> */
    public static function matchingName(string $name): Builder
    {
        return static::query()->whereRaw('LOWER(name) = LOWER(?)', [$name]);
    }

    protected function casts(): array
    {
        return ['requires_note' => 'boolean', 'is_active' => 'boolean'];
    }
}
