<?php

namespace Database\Seeders;

use App\Scheduling\Models\AttendanceRecord;
use Illuminate\Database\Seeder;

class AttendanceRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AttendanceRecord::factory()->create();
    }
}
