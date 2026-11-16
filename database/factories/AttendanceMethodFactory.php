<?php

namespace Database\Factories;

use App\Organization\Models\AttendanceMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AttendanceMethod> */
class AttendanceMethodFactory extends Factory
{
    protected $model = AttendanceMethod::class;

    public function definition(): array
    {
        return [
            'name' => 'Metode '.fake()->unique()->numerify('#####'),
            'requires_location' => false,
            'requires_face_verification' => false,
            'requires_registered_device' => false,
            'is_active' => true,
        ];
    }
}
