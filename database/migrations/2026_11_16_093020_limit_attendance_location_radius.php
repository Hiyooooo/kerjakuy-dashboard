<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('attendance_locations')->where('radius_meter', '>', 1000)->exists()) {
            throw new RuntimeException('Sesuaikan radius lokasi absen yang melebihi 1.000 meter sebelum menjalankan migrasi.');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendance_locations ADD CONSTRAINT attendance_locations_maximum_radius CHECK (radius_meter <= 1000)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendance_locations DROP CONSTRAINT attendance_locations_maximum_radius');
        }
    }
};
