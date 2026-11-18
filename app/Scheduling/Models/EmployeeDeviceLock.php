<?php

namespace App\Scheduling\Models;

use App\Access\Models\User;
use App\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['employee_id', 'registered_device_id', 'locked_by_user_id', 'locked_at'])]
class EmployeeDeviceLock extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by_user_id');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by_user_id');
    }

    protected function casts(): array
    {
        return ['locked_at' => 'immutable_datetime', 'released_at' => 'immutable_datetime'];
    }
}
