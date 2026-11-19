<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_request_details', function (Blueprint $table): void {
            $table->foreignId('request_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('employee_shift_schedule_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->date('overtime_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('total_minutes');
            $table->text('reason');
            $table->timestamps();
        });

        Schema::create('shift_swap_request_details', function (Blueprint $table): void {
            $table->foreignId('request_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('target_employee_id')->index()->constrained('employees')->restrictOnDelete();
            $table->date('requester_work_date');
            $table->date('target_work_date');
            $table->foreignId('requester_work_shift_id')->constrained('work_shifts')->restrictOnDelete();
            $table->foreignId('target_work_shift_id')->constrained('work_shifts')->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_swap_request_details');
        Schema::dropIfExists('overtime_request_details');
    }
};
