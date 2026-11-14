<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_times', function (Blueprint $table): void {
            $table->smallInteger('check_in_start_day')->default(0);
            $table->smallInteger('expected_check_out_day')->default(0);
            $table->smallInteger('check_out_end_day')->default(0);
        });

        DB::table('attendance_times')->whereColumn('expected_check_out', '<', 'expected_check_in')
            ->update(['expected_check_out_day' => 1]);
        DB::table('attendance_times')->whereColumn('check_out_end', '<', 'expected_check_in')
            ->update(['check_out_end_day' => 1]);

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendance_times DROP CONSTRAINT attendance_times_valid_window');
            DB::statement("ALTER TABLE attendance_times ADD CONSTRAINT attendance_times_valid_window CHECK (
                check_in_start_day IN (-1, 0)
                AND (check_in_start_day = -1 OR check_in_start <= expected_check_in)
                AND expected_check_out_day IN (0, 1)
                AND expected_check_out - expected_check_in + expected_check_out_day * INTERVAL '1 day'
                    BETWEEN INTERVAL '1 second' AND INTERVAL '23 hours 59 minutes 59 seconds'
                AND late_tolerance_minutes BETWEEN 0 AND 1439
                AND check_out_end_day IN (0, 1)
                AND (
                    (check_out_end IS NULL AND check_out_end_day = 0)
                    OR (check_out_end IS NOT NULL
                        AND (check_out_end_day, check_out_end) >= (expected_check_out_day, expected_check_out))
                )
            )");
        }
    }

    public function down(): void
    {
        $wouldLoseDay = DB::table('attendance_times')->where('check_in_start_day', '<>', 0)
            ->orWhereRaw('expected_check_out_day <> CASE WHEN expected_check_out < expected_check_in THEN 1 ELSE 0 END')
            ->orWhereRaw('check_out_end_day <> CASE WHEN check_out_end < expected_check_in THEN 1 ELSE 0 END')
            ->exists();

        if ($wouldLoseDay) {
            throw new RuntimeException('Day offsets cannot be removed without changing existing attendance windows.');
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE attendance_times DROP CONSTRAINT attendance_times_valid_window');
            DB::statement("ALTER TABLE attendance_times ADD CONSTRAINT attendance_times_valid_window CHECK (
                check_in_start <= expected_check_in
                AND expected_check_out <> expected_check_in
                AND late_tolerance_minutes BETWEEN 0 AND 1439
                AND (
                    check_out_end IS NULL
                    OR check_out_end - expected_check_in + CASE WHEN check_out_end < expected_check_in THEN INTERVAL '1 day' ELSE INTERVAL '0' END
                    >= expected_check_out - expected_check_in + CASE WHEN expected_check_out < expected_check_in THEN INTERVAL '1 day' ELSE INTERVAL '0' END
                )
            )");
        }

        Schema::table('attendance_times', function (Blueprint $table): void {
            $table->dropColumn(['check_in_start_day', 'expected_check_out_day', 'check_out_end_day']);
        });
    }
};
