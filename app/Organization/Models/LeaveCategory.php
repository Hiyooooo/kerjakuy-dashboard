<?php

namespace App\Organization\Models;

use Database\Factories\LeaveCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'description', 'is_active'])]
#[UseFactory(LeaveCategoryFactory::class)]
class LeaveCategory extends Model
{
    /** @use HasFactory<LeaveCategoryFactory> */
    use HasFactory;

    public const string DUPLICATE_MESSAGE = 'Nama kategori cuti ini sudah dipakai.';

    /** @return Builder<static> */
    public static function matchingName(string $name): Builder
    {
        return static::query()->whereRaw('LOWER(name) = LOWER(?)', [$name]);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
