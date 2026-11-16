<?php

namespace Database\Seeders;

use App\Organization\Models\AttendanceMethod;
use Illuminate\Database\Seeder;

class AttendanceMethodSeeder extends Seeder
{
    public function run(): void
    {
        $name = 'Absen Kantor';

        AttendanceMethod::matchingName($name)->firstOrCreate([], [
            'name' => $name,
            'requires_location' => true,
            'requires_face_verification' => true,
            'requires_registered_device' => true,
        ]);
    }
}
