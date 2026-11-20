<?php

namespace App\Employee\Models;

use App\Access\Models\User;
use Database\Factories\Employee\Models\EmployeeCoachingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['coaching_date', 'subject', 'description', 'follow_up'])]
#[UseFactory(EmployeeCoachingFactory::class)]
class EmployeeCoaching extends Model
{
    /** @use HasFactory<EmployeeCoachingFactory> */
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return ['coaching_date' => 'immutable_date'];
    }
}
