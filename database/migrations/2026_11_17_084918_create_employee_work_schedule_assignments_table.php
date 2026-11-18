<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_work_schedule_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_schedule_template_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by_user_id')->constrained('users')->restrictOnDelete();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->unique(['employee_id', 'effective_start_date'], 'employee_default_schedule_start_unique');
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE employee_work_schedule_assignments
                ADD CHECK (effective_end_date >= effective_start_date),
                ADD CONSTRAINT employee_default_schedule_no_overlap EXCLUDE USING gist (
                    numrange(employee_id::numeric, employee_id::numeric, '[]') WITH &&,
                    daterange(effective_start_date, effective_end_date, '[]') WITH &&
                )
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_work_schedule_assignments');
    }
};
