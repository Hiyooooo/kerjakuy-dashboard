<?php

namespace Database\Seeders;

use App\Employee\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        Employee::firstOrCreate(['employee_code' => 'DEMO-001'], ['full_name' => 'Pegawai Demo']);
    }
}
