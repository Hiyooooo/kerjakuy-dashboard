<?php

namespace Database\Seeders;

use App\Scheduling\Models\WorkScheduleTemplate;
use Illuminate\Database\Seeder;

class WorkScheduleTemplateSeeder extends Seeder
{
    public function run(): void
    {
        if (! WorkScheduleTemplate::where('name', 'Template demo')->exists()) {
            WorkScheduleTemplate::factory()->create(['name' => 'Template demo']);
        }
    }
}
