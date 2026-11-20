<?php

namespace Database\Factories\Employee\Models;

use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeCoaching;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeCoaching> */
class EmployeeCoachingFactory extends Factory
{
    protected $model = EmployeeCoaching::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'coaching_date' => '2025-04-16',
            'subject' => 'Evaluasi orientasi pegawai',
            'created_by_user_id' => User::factory(),
        ];
    }
}
