<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('office_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->integer('radius_meter');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE attendance_locations
                ADD CONSTRAINT attendance_locations_latitude_range CHECK (latitude BETWEEN -90 AND 90),
                ADD CONSTRAINT attendance_locations_longitude_range CHECK (longitude BETWEEN -180 AND 180),
                ADD CONSTRAINT attendance_locations_positive_radius CHECK (radius_meter > 0)
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_locations');
    }
};
