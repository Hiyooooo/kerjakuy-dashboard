<?php

namespace App\Organization\Models;

use Database\Factories\HolidayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['office_id', 'name', 'holiday_date', 'description'])]
#[UseFactory(HolidayFactory::class)]
class Holiday extends Model
{
    /** @use HasFactory<HolidayFactory> */
    use HasFactory;

    public const string DUPLICATE_MESSAGE = 'Hari libur dengan nama, tanggal, dan cakupan ini sudah ada.';

    /** @return BelongsTo<Office, $this> */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    protected function casts(): array
    {
        return ['holiday_date' => 'immutable_date', 'has_manual_origin' => 'boolean'];
    }
}
