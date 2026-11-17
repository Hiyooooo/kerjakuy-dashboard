<?php

namespace Database\Seeders;

use App\Organization\Models\AttendanceTime;
use App\Scheduling\Models\WorkShift;
use Illuminate\Database\Seeder;

class WorkShiftSeeder extends Seeder
{
    public function run(): void
    {
        $time = AttendanceTime::where('is_active', true)->orderBy('id')->first();

        if ($time !== null) {
            WorkShift::firstOrCreate(['code' => 'DEMO-SHIFT'], [
                'attendance_time_id' => $time->id,
                'name' => 'Shift demo',
                'is_active' => false,
            ]);
        }
    }
}
