<?php

namespace Database\Factories;

use App\Organization\Models\AttendanceTime;
use App\Scheduling\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkShift> */
class WorkShiftFactory extends Factory
{
    protected $model = WorkShift::class;

    public function definition(): array
    {
        return ['attendance_time_id' => AttendanceTime::factory(), 'code' => fake()->unique()->numerify('SHIFT-#####'), 'name' => 'Shift kerja'];
    }
}
