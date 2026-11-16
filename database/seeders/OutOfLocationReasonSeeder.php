<?php

namespace Database\Seeders;

use App\Organization\Models\OutOfLocationReason;
use Illuminate\Database\Seeder;

class OutOfLocationReasonSeeder extends Seeder
{
    public function run(): void
    {
        $name = 'Tugas lapangan';

        OutOfLocationReason::matchingName($name)->firstOrCreate([], ['name' => $name]);
    }
}
