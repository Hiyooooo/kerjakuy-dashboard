<?php

namespace Database\Factories\Employee\Models;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeFamilyMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeFamilyMember>
 */
class EmployeeFamilyMemberFactory extends Factory
{
    protected $model = EmployeeFamilyMember::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'full_name' => fake()->name(),
            'relationship' => 'Anak',
            'is_tax_dependent' => false,
        ];
    }
}
