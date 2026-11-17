<?php

namespace Database\Factories;

use App\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_code' => fake()->unique()->numerify('PGW-######'),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
