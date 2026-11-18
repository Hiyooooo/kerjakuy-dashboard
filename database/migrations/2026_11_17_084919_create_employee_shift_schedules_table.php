<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_shift_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_shift_id')->index()->constrained()->restrictOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('office_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->date('work_date');
            $table->enum('status', ['scheduled', 'cancelled'])->default('scheduled');
            $table->text('reason')->nullable();
            $table->unique(['employee_id', 'work_date']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_shift_schedules');
    }
};
