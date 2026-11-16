<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('holidays', function (Blueprint $table): void {
            $table->string('official_type', 20)->nullable();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE holidays ADD CONSTRAINT holidays_official_scope_check
                CHECK (official_type IS NULL OR (office_id IS NULL AND official_type IN ('national_holiday', 'joint_leave')))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE holidays DROP CONSTRAINT holidays_official_scope_check');
        }

        Schema::table('holidays', function (Blueprint $table): void {
            $table->dropColumn('official_type');
        });
    }
};
