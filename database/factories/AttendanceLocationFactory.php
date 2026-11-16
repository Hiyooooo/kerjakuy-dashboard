<?php

namespace Database\Factories;

use App\Organization\Models\AttendanceLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceLocation> */
class AttendanceLocationFactory extends Factory
{
    protected $model = AttendanceLocation::class;

    public function definition(): array
    {
        return [
            'name' => 'Lokasi '.fake()->unique()->numerify('#####'),
            'latitude' => -6.1753924,
            'longitude' => 106.8271528,
            'radius_meter' => 100,
            'is_active' => true,
        ];
    }
}
