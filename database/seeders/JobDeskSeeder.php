<?php

namespace Database\Seeders;

use App\Organization\Models\JobDesk;
use App\Organization\Models\Position;
use Illuminate\Database\Seeder;

class JobDeskSeeder extends Seeder
{
    public function run(): void
    {
        $position = Position::firstOrCreate(['code' => 'STAF-HR'], ['name' => 'Staf HR']);

        JobDesk::firstOrCreate(['position_id' => $position->id, 'title' => 'Administrasi kepegawaian'], [
            'job_description' => 'Memelihara data dan dokumen kepegawaian.',
            'job_specification' => 'Mampu mengelola dokumen dan bekerja dengan spreadsheet.',
        ]);
    }
}
