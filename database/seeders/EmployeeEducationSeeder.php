<?php

namespace Database\Seeders;

use App\Employee\Models\EmployeeEducation;
use Illuminate\Database\Seeder;

class EmployeeEducationSeeder extends Seeder
{
    public function run(): void
    {
        EmployeeEducation::factory()->create();
    }
}
