<?php

namespace Database\Seeders;

use App\Employee\Models\EmployeeCoaching;
use Illuminate\Database\Seeder;

class EmployeeCoachingSeeder extends Seeder
{
    public function run(): void
    {
        EmployeeCoaching::factory()->create();
    }
}
