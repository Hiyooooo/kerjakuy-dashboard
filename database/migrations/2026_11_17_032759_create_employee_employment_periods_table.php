<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_employment_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->enum('employment_type', ['permanent', 'contract', 'intern', 'freelance']);
            $table->enum('status', ['active', 'inactive', 'resigned'])->default('active');
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'effective_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employment_periods');
    }
};
