<?php

namespace Database\Factories\Employee\Models;

use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<EmployeeRequest> */
class EmployeeRequestFactory extends Factory
{
    protected $model = EmployeeRequest::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'request_number' => 'REQ-'.Str::ulid(),
            'request_type' => 'leave',
            'submitted_at' => now(),
        ];
    }
}
