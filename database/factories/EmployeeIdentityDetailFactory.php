<?php

namespace Database\Factories;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeIdentityDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeIdentityDetail> */
class EmployeeIdentityDetailFactory extends Factory
{
    protected $model = EmployeeIdentityDetail::class;

    public function definition(): array
    {
        return ['employee_id' => Employee::factory(), 'gender' => 'female'];
    }

    public function configure(): static
    {
        return $this->afterMaking(fn (EmployeeIdentityDetail $identity) => $identity->setNationalId(fake()->unique()->numerify('000000##########')));
    }
}
