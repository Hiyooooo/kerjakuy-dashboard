<?php

namespace Database\Factories;

use App\Organization\Models\JobDesk;
use App\Organization\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<JobDesk> */
class JobDeskFactory extends Factory
{
    protected $model = JobDesk::class;

    public function definition(): array
    {
        return [
            'position_id' => fn (): int => Position::create([
                'code' => Str::random(10),
                'name' => fake()->jobTitle(),
            ])->id,
            'title' => fake()->sentence(3),
            'job_description' => fake()->sentence(),
            'job_specification' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
