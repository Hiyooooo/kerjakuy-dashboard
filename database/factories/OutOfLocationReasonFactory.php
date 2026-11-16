<?php

namespace Database\Factories;

use App\Organization\Models\OutOfLocationReason;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OutOfLocationReason> */
class OutOfLocationReasonFactory extends Factory
{
    protected $model = OutOfLocationReason::class;

    public function definition(): array
    {
        return [
            'name' => 'Alasan '.fake()->unique()->numerify('#####'),
            'requires_note' => true,
            'is_active' => true,
        ];
    }
}
