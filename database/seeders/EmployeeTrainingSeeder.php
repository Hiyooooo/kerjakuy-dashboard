<?php

namespace Database\Seeders;

use App\Employee\Models\EmployeeTraining;
use Illuminate\Database\Seeder;

class EmployeeTrainingSeeder extends Seeder
{
    public function run(): void
    {
        EmployeeTraining::factory()->create();
    }
}
