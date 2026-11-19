<?php

namespace App\Scheduling\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['device_uuid', 'device_name', 'platform', 'app_version', 'registered_by_user_id', 'registered_by_name'])]
class RegisteredDevice extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'registered'];

    public function deviceLocks(): HasMany
    {
        return $this->hasMany(EmployeeDeviceLock::class);
    }

    public function activeLock(): HasOne
    {
        return $this->hasOne(EmployeeDeviceLock::class)->whereNull('released_at');
    }

    protected function casts(): array
    {
        return ['last_used_at' => 'immutable_datetime'];
    }
}
