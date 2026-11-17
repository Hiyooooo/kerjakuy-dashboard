<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('LOCK TABLE employee_face_profiles IN SHARE ROW EXCLUSIVE MODE');
        }

        $now = now();
        DB::update(<<<'SQL'
            UPDATE employee_face_profiles SET deactivated_at = ?, updated_at = ?
            WHERE id IN (
                SELECT id FROM (
                    SELECT id, ROW_NUMBER() OVER (PARTITION BY employee_id ORDER BY uploaded_at DESC, id DESC) AS profile_rank
                    FROM employee_face_profiles WHERE deactivated_at IS NULL
                ) AS ranked WHERE profile_rank > 1
            )
            SQL, [$now, $now]);

        DB::statement('CREATE UNIQUE INDEX employee_face_profiles_one_active ON employee_face_profiles (employee_id) WHERE deactivated_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX employee_face_profiles_one_active');
    }
};
