<?php

namespace Database\Factories;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeEmploymentPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeEmploymentPeriod> */
class EmployeeEmploymentPeriodFactory extends Factory
{
    protected $model = EmployeeEmploymentPeriod::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'employment_type' => 'contract',
            'status' => 'active',
            'effective_start_date' => '2026-01-01',
        ];
    }
}
