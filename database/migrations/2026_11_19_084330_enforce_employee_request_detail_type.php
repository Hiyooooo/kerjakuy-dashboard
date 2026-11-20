<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const array DETAIL_TABLES = [
        'leave_request_details',
        'late_request_details',
        'overtime_request_details',
        'shift_swap_request_details',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE FUNCTION check_employee_request_detail() RETURNS trigger
            LANGUAGE plpgsql AS $$
            DECLARE
                affected_ids bigint[];
                affected_id bigint;
                expected_type text;
                detail_types text[];
            BEGIN
                IF TG_TABLE_NAME = 'requests' THEN
                    affected_ids := ARRAY[NEW.id];
                ELSIF TG_OP = 'DELETE' THEN
                    affected_ids := ARRAY[OLD.request_id];
                ELSIF TG_OP = 'UPDATE' THEN
                    affected_ids := ARRAY[OLD.request_id, NEW.request_id];
                ELSE
                    affected_ids := ARRAY[NEW.request_id];
                END IF;

                FOREACH affected_id IN ARRAY affected_ids LOOP
                    SELECT request_type INTO expected_type FROM requests WHERE id = affected_id FOR NO KEY UPDATE;
                    IF NOT FOUND THEN
                        CONTINUE;
                    END IF;

                    detail_types := ARRAY(
                        SELECT 'leave'::text FROM leave_request_details WHERE request_id = affected_id
                        UNION ALL SELECT 'late' FROM late_request_details WHERE request_id = affected_id
                        UNION ALL SELECT 'overtime' FROM overtime_request_details WHERE request_id = affected_id
                        UNION ALL SELECT 'shift_swap' FROM shift_swap_request_details WHERE request_id = affected_id
                    );

                    IF detail_types <> ARRAY[expected_type] THEN
                        RAISE EXCEPTION 'Request % must have exactly one % detail', affected_id, expected_type
                            USING ERRCODE = '23514', CONSTRAINT = 'employee_request_detail_type';
                    END IF;
                END LOOP;

                RETURN NULL;
            END;
            $$;
            SQL);

        DB::statement('CREATE CONSTRAINT TRIGGER employee_request_detail_type
            AFTER INSERT OR UPDATE OF request_type ON requests
            DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION check_employee_request_detail()');

        foreach (self::DETAIL_TABLES as $table) {
            DB::statement("CREATE CONSTRAINT TRIGGER employee_request_detail_type
                AFTER INSERT OR UPDATE OF request_id OR DELETE ON {$table}
                DEFERRABLE INITIALLY DEFERRED FOR EACH ROW EXECUTE FUNCTION check_employee_request_detail()");
        }

        // Queue existing rows for the same validation before committing the migration.
        DB::statement('UPDATE requests SET request_type = request_type');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['requests', ...self::DETAIL_TABLES] as $table) {
            DB::statement("DROP TRIGGER employee_request_detail_type ON {$table}");
        }

        DB::statement('DROP FUNCTION check_employee_request_detail()');
    }
};
