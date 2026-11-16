<?php

namespace Database\Factories;

use App\Organization\Models\Holiday;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Holiday> */
class HolidayFactory extends Factory
{
    protected $model = Holiday::class;

    public function definition(): array
    {
        return [
            'office_id' => null,
            'name' => 'Hari libur '.fake()->unique()->numerify('#####'),
            'holiday_date' => fake()->date(),
            'description' => fake()->sentence(),
        ];
    }
}
