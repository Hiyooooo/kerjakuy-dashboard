<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_family_tax_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_family_member_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->unique(['employee_family_member_id', 'start_date'], 'employee_family_tax_period_start_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE employee_family_tax_periods
                ADD CONSTRAINT employee_family_tax_period_date_order CHECK (end_date >= start_date)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_family_tax_periods');
    }
};
