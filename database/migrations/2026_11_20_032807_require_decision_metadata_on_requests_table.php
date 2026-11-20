<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            UPDATE requests
            SET reviewed_by_name = users.name
            FROM users
            WHERE requests.reviewed_by_user_id = users.id
                AND requests.status <> 'pending'
                AND requests.reviewed_by_name IS NULL
            SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE requests ADD CONSTRAINT requests_decision_metadata_check
            CHECK (status = 'pending' OR (reviewed_at IS NOT NULL AND reviewed_by_name IS NOT NULL))
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE requests DROP CONSTRAINT requests_decision_metadata_check');
        }
    }
};
