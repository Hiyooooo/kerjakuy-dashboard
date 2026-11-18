<?php

namespace Database\Seeders;

use App\Scheduling\Models\FaceRecognitionLog;
use Illuminate\Database\Seeder;

class FaceRecognitionLogSeeder extends Seeder
{
    public function run(): void
    {
        FaceRecognitionLog::factory()->create();
    }
}
