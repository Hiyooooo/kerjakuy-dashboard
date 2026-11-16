<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX leave_categories_name_lower_unique ON leave_categories (LOWER(name))');
        DB::statement('CREATE UNIQUE INDEX holidays_global_unique ON holidays (holiday_date, name) WHERE office_id IS NULL');
        DB::statement('CREATE UNIQUE INDEX holidays_office_unique ON holidays (office_id, holiday_date, name) WHERE office_id IS NOT NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX holidays_office_unique');
        DB::statement('DROP INDEX holidays_global_unique');
        DB::statement('DROP INDEX leave_categories_name_lower_unique');
    }
};
