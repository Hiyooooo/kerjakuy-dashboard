<?php

namespace Database\Factories;

use App\Organization\Models\LeaveCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaveCategory> */
class LeaveCategoryFactory extends Factory
{
    protected $model = LeaveCategory::class;

    public function definition(): array
    {
        return [
            'name' => 'Kategori cuti '.fake()->unique()->numerify('#####'),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
