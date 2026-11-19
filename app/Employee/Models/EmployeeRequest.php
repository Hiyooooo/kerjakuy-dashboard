<?php

namespace App\Employee\Models;

use App\Access\Models\User;
use Database\Factories\Employee\Models\EmployeeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

#[Fillable(['request_number', 'request_type', 'submitted_at'])]
#[UseFactory(EmployeeRequestFactory::class)]
class EmployeeRequest extends Model
{
    /** @use HasFactory<EmployeeRequestFactory> */
    use HasFactory;

    protected $table = 'requests';

    protected $attributes = ['status' => 'pending'];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function ensurePending(): void
    {
        if ($this->status !== 'pending') {
            throw ValidationException::withMessages(['approval' => 'Permohonan ini sudah diproses.']);
        }
    }

    public function recordDecision(string $decision, User $actor, ?string $note): void
    {
        $this->forceFill([
            'status' => $decision,
            'reviewed_by_user_id' => $actor->id,
            'reviewed_by_name' => $actor->name,
            'reviewer_note' => $note,
            'reviewed_at' => now(),
        ])->saveOrFail();
    }

    protected function casts(): array
    {
        return ['submitted_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime'];
    }
}
