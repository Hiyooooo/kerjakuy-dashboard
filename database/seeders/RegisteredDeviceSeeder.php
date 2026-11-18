<?php

namespace Database\Seeders;

use App\Scheduling\Models\RegisteredDevice;
use Illuminate\Database\Seeder;

class RegisteredDeviceSeeder extends Seeder
{
    public function run(): void
    {
        RegisteredDevice::query()->firstOrCreate(
            ['device_uuid' => '97fbffda-cfe8-4c30-a7c0-c6f21c3bcd9d'],
            ['device_name' => 'Perangkat demo'],
        );
    }
}
