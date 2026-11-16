<?php

namespace Database\Seeders;

use App\Organization\Models\AttendanceLocation;
use App\Organization\Models\Office;
use Illuminate\Database\Seeder;

class AttendanceLocationSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Office::whereNotNull('latitude')->whereNotNull('longitude')->cursor() as $office) {
            AttendanceLocation::firstOrCreate(
                ['office_id' => $office->id, 'name' => $office->name],
                [
                    'latitude' => $office->latitude,
                    'longitude' => $office->longitude,
                    'radius_meter' => 100,
                    'is_active' => false,
                ],
            );
        }
    }
}
