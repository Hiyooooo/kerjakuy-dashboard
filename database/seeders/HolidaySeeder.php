<?php

namespace Database\Seeders;

use App\Organization\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        Holiday::firstOrCreate([
            'office_id' => null,
            'name' => 'Contoh hari libur perusahaan',
            'holiday_date' => now()->startOfYear()->toDateString(),
        ]);
    }
}
