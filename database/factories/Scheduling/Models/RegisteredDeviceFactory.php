<?php

namespace Database\Factories\Scheduling\Models;

use App\Scheduling\Models\RegisteredDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RegisteredDevice> */
class RegisteredDeviceFactory extends Factory
{
    protected $model = RegisteredDevice::class;

    public function definition(): array
    {
        return ['device_uuid' => fake()->uuid()];
    }
}
