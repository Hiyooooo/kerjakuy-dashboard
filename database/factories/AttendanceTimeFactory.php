<?php

namespace Database\Factories;

use App\Organization\Models\AttendanceTime;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceTime> */
class AttendanceTimeFactory extends Factory
{
    protected $model = AttendanceTime::class;

    public function definition(): array
    {
        return [
            'name' => 'Waktu absen '.fake()->unique()->numerify('#####'),
            'check_in_start' => '07:30',
            'expected_check_in' => '08:00',
            'expected_check_out' => '17:00',
            'check_out_end' => '18:00',
            'late_tolerance_minutes' => 0,
            'is_active' => true,
        ];
    }
}
