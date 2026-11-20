<?php

namespace App\Employee\Models;

use App\Access\Models\User;
use App\Scheduling\Models\EmployeeShiftSchedule;
use App\Scheduling\Models\EmployeeWorkScheduleAssignment;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['employee_code', 'full_name', 'email', 'phone', 'birth_date', 'address'])]
#[UseFactory(EmployeeFactory::class)]
class Employee extends Model
{
    #[Scope]
    protected function matchingSearch(Builder $query, string $search): void
    {
        $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
        $query->where(fn (Builder $match) => $match
            ->whereRaw("LOWER(full_name) LIKE LOWER(?) ESCAPE '!'", [$pattern])
            ->orWhereRaw("LOWER(employee_code) LIKE LOWER(?) ESCAPE '!'", [$pattern]));
    }

    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(EmployeeWorkScheduleAssignment::class);
    }

    public function shiftOverrides(): HasMany
    {
        return $this->hasMany(EmployeeShiftSchedule::class);
    }

    public function identityDetail(): HasOne
    {
        return $this->hasOne(EmployeeIdentityDetail::class);
    }

    public function familyMembers(): HasMany
    {
        return $this->hasMany(EmployeeFamilyMember::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class);
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(EmployeeTraining::class);
    }

    public function coachings(): HasMany
    {
        return $this->hasMany(EmployeeCoaching::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(EmployeeEmploymentPeriod::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(EmployeeRequest::class);
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
