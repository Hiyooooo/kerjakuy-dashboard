<?php

namespace Database\Factories\Scheduling\Models;

use App\Employee\Models\Employee;
use App\Scheduling\Models\EmployeeDeviceLock;
use App\Scheduling\Models\RegisteredDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeDeviceLock> */
class EmployeeDeviceLockFactory extends Factory
{
    protected $model = EmployeeDeviceLock::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'registered_device_id' => RegisteredDevice::factory(),
            'locked_at' => now(),
        ];
    }
}
