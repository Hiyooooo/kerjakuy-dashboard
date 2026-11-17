<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE employee_employment_periods
            ADD CONSTRAINT employee_employment_period_dates_ordered
                CHECK (effective_end_date >= effective_start_date)
            SQL);

        // Singleton ranges overlap only for the same employee and use PostgreSQL's built-in GiST operators.
        DB::statement(<<<'SQL'
            ALTER TABLE employee_employment_periods
            ADD CONSTRAINT employee_employment_periods_do_not_overlap
                EXCLUDE USING gist (
                    numrange(employee_id::numeric, employee_id::numeric, '[]') WITH &&,
                    daterange(effective_start_date, effective_end_date, '[]') WITH &&
                )
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE employee_employment_periods
                DROP CONSTRAINT employee_employment_periods_do_not_overlap,
                DROP CONSTRAINT employee_employment_period_dates_ordered
                SQL);
        }
    }
};
