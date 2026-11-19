<?php

namespace Database\Seeders;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use Illuminate\Database\Seeder;

class EmployeeRequestSeeder extends Seeder
{
    public function run(): void
    {
        $employee = Employee::factory()->create();

        foreach (['leave', 'late', 'overtime', 'shift_swap'] as $type) {
            EmployeeRequest::factory()->for($employee)->create(['request_type' => $type]);
        }
    }
}
