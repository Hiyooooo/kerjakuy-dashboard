<?php

namespace Database\Seeders;

use App\Employee\Models\Employee;
use Illuminate\Database\Seeder;

class EmployeeIdentityDetailSeeder extends Seeder
{
    public function run(): void
    {
        $employee = Employee::where('employee_code', 'DEMO-001')->firstOrFail();

        if (! $employee->identityDetail()->exists()) {
            $identity = $employee->identityDetail()->make(['gender' => 'female']);
            $identity->setNationalId('0000000000000001');
            $identity->saveOrFail();
        }
    }
}
