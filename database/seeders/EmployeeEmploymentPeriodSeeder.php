<?php

namespace Database\Seeders;

use App\Employee\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeEmploymentPeriodSeeder extends Seeder
{
    public function run(): void
    {
        $employee = Employee::where('employee_code', 'DEMO-001')->firstOrFail();

        if (! $employee->periods()->exists()) {
            $employee->periods()->create([
                'employment_type' => 'contract',
                'status' => 'active',
                'effective_start_date' => '2026-01-01',
            ]);
        }
    }
}
