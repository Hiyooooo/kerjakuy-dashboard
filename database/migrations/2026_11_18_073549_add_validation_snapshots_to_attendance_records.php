<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->timestamp('check_out_deadline_at')->nullable();
            $table->decimal('location_latitude_snapshot', 10, 7)->nullable();
            $table->decimal('location_longitude_snapshot', 10, 7)->nullable();
            $table->unsignedInteger('location_radius_snapshot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table): void {
            $table->dropColumn(['check_out_deadline_at', 'location_latitude_snapshot', 'location_longitude_snapshot', 'location_radius_snapshot']);
        });
    }
};
