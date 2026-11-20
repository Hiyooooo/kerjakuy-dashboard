<?php

namespace Database\Factories\Employee\Models;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeEducation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeEducation> */
class EmployeeEducationFactory extends Factory
{
    protected $model = EmployeeEducation::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'institution_name' => 'Universitas Contoh',
            'education_level' => 'S1',
            'major' => 'Manajemen',
            'start_year' => 2019,
            'graduation_year' => 2023,
        ];
    }
}
