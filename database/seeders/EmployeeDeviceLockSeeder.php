<?php

namespace Database\Seeders;

use App\Scheduling\Models\EmployeeDeviceLock;
use Illuminate\Database\Seeder;

class EmployeeDeviceLockSeeder extends Seeder
{
    public function run(): void
    {
        EmployeeDeviceLock::factory()->create();
    }
}
