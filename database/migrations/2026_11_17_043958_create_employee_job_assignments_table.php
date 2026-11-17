<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_job_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('office_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('position_id')->index()->constrained()->restrictOnDelete();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->string('change_type', 50);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'effective_start_date']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE employee_job_assignments
                ADD CONSTRAINT employee_job_assignment_date_order
                    CHECK (effective_end_date >= effective_start_date),
                ADD CONSTRAINT employee_job_assignments_do_not_overlap
                    EXCLUDE USING gist (
                        numrange(employee_id::numeric, employee_id::numeric, '[]') WITH &&,
                        daterange(effective_start_date, effective_end_date, '[]') WITH &&
                    )
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_job_assignments');
    }
};
