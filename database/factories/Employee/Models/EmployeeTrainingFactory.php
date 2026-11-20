<?php

namespace Database\Factories\Employee\Models;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeTraining;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeTraining> */
class EmployeeTrainingFactory extends Factory
{
    protected $model = EmployeeTraining::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'title' => 'Pelatihan Keselamatan Kerja',
            'provider' => 'Pusat Pelatihan Contoh',
            'start_date' => '2025-04-14',
            'end_date' => '2025-04-15',
        ];
    }
}
