<?php

namespace App\Scheduling\Models;

use App\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Hidden(['captured_photo_path'])]
class FaceRecognitionLog extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return ['captured_at' => 'immutable_datetime', 'match_score' => 'decimal:2', 'minimum_match_score' => 'decimal:2'];
    }
}
