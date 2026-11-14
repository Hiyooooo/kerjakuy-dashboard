<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_times', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->time('check_in_start');
            $table->time('expected_check_in');
            $table->time('expected_check_out');
            $table->time('check_out_end')->nullable();
            $table->integer('late_tolerance_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendance_times ADD CONSTRAINT attendance_times_valid_window CHECK (
                check_in_start <= expected_check_in
                AND expected_check_out <> expected_check_in
                AND late_tolerance_minutes BETWEEN 0 AND 1439
                AND (
                    check_out_end IS NULL
                    OR check_out_end - expected_check_in + CASE WHEN check_out_end < expected_check_in THEN INTERVAL \'1 day\' ELSE INTERVAL \'0\' END
                    >= expected_check_out - expected_check_in + CASE WHEN expected_check_out < expected_check_in THEN INTERVAL \'1 day\' ELSE INTERVAL \'0\' END
                )
            )');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_times');
    }
};
