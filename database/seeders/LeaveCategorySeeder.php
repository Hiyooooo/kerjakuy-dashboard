<?php

namespace Database\Seeders;

use App\Organization\Models\LeaveCategory;
use Illuminate\Database\Seeder;

class LeaveCategorySeeder extends Seeder
{
    public function run(): void
    {
        $name = 'Cuti tahunan';

        LeaveCategory::matchingName($name)->firstOrCreate([], ['name' => $name]);
    }
}
