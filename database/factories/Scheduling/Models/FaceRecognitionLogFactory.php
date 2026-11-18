<?php

namespace Database\Factories\Scheduling\Models;

use App\Employee\Models\Employee;
use App\Scheduling\Models\FaceRecognitionLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FaceRecognitionLog> */
class FaceRecognitionLogFactory extends Factory
{
    protected $model = FaceRecognitionLog::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'verification_type' => 'check_in',
            'status' => 'failed',
            'failure_reason' => 'Profil referensi wajah tidak tersedia.',
            'captured_at' => now(),
        ];
    }
}
