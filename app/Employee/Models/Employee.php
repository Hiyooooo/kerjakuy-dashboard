<?php

namespace App\Employee\Models;

use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['employee_code', 'full_name', 'email', 'phone', 'birth_date', 'address'])]
#[UseFactory(EmployeeFactory::class)]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    public function identityDetail(): HasOne
    {
        return $this->hasOne(EmployeeIdentityDetail::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(EmployeeEmploymentPeriod::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeJobAssignment::class);
    }

    public function faceProfiles(): HasMany
    {
        return $this->hasMany(EmployeeFaceProfile::class);
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }

    protected function casts(): array
    {
        return ['birth_date' => 'immutable_date'];
    }
}
