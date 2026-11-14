<?php

namespace Database\Seeders;

use App\Organization\Models\AttendanceTime;
use Illuminate\Database\Seeder;

class AttendanceTimeSeeder extends Seeder
{
    public function run(): void
    {
        AttendanceTime::firstOrCreate(['name' => 'Jam kantor'], [
            'check_in_start' => '07:30',
            'expected_check_in' => '08:00',
            'expected_check_out' => '17:00',
            'check_out_end' => '18:00',
            'late_tolerance_minutes' => 0,
        ]);
    }
}
