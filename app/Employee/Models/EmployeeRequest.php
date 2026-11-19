<?php

namespace App\Employee\Models;

use App\Access\Models\User;
use Database\Factories\Employee\Models\EmployeeRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected function casts(): array
    {
        return ['submitted_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime'];
    }
}
