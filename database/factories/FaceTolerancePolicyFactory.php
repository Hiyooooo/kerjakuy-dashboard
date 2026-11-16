<?php

namespace Database\Factories;

use App\Access\Models\User;
use App\Organization\Models\FaceTolerancePolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FaceTolerancePolicy> */
class FaceTolerancePolicyFactory extends Factory
{
    protected $model = FaceTolerancePolicy::class;

    public function definition(): array
    {
        $date = fake()->unique()->date();

        return [
            'minimum_match_score' => 85,
            'effective_start_date' => $date,
            'effective_end_date' => $date,
            'created_by_user_id' => User::factory(),
        ];
    }
}
