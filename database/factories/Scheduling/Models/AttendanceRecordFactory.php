<?php

namespace Database\Factories\Scheduling\Models;

use App\Employee\Models\Employee;
use App\Organization\Models\AttendanceMethod;
use App\Scheduling\Models\AttendanceRecord;
use App\Scheduling\Models\WorkShift;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceRecord> */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'work_shift_id' => WorkShift::factory(),
            'attendance_method_id' => AttendanceMethod::factory(),
            'work_date' => '2026-01-05',
            'scheduled_check_in_at' => '2026-01-05 01:00:00',
            'scheduled_check_out_at' => '2026-01-05 10:00:00',
            'check_in_at' => '2026-01-05 01:00:00',
            'status' => 'incomplete',
        ];
    }
}
