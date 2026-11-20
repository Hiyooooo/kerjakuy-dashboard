<?php

namespace App\Employee\Models;

use Database\Factories\Employee\Models\EmployeeEducationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['institution_name', 'education_level', 'major', 'start_year', 'graduation_year', 'certificate_number'])]
#[UseFactory(EmployeeEducationFactory::class)]
class EmployeeEducation extends Model
{
    /** @use HasFactory<EmployeeEducationFactory> */
    use HasFactory;

    protected $table = 'employee_educations';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return ['start_year' => 'integer', 'graduation_year' => 'integer'];
    }
}
