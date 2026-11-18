<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('employee_shift_schedule_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('work_shift_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_method_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_location_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('registered_device_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('out_of_location_reason_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('check_out_reason_id')->nullable()->constrained('out_of_location_reasons')->restrictOnDelete();
            $table->date('work_date');
            $table->timestamp('scheduled_check_in_at');
            $table->timestamp('scheduled_check_out_at');
            foreach (['check_in', 'check_out'] as $event) {
                $table->timestamp($event.'_at')->nullable();
                $table->decimal($event.'_latitude', 10, 7)->nullable();
                $table->decimal($event.'_longitude', 10, 7)->nullable();
                $table->string($event.'_selfie_path')->nullable();
                $table->foreignId($event.'_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            }
            $table->unsignedInteger('late_minutes')->default(0);
            $table->decimal('distance_meter', 10, 2)->nullable();
            $table->decimal('check_out_distance_meter', 10, 2)->nullable();
            $table->text('out_of_location_note')->nullable();
            $table->text('check_out_location_note')->nullable();
            $table->enum('status', ['present', 'late', 'absent', 'incomplete', 'offsite'])->default('present');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'work_date']);
            $table->index(['work_date', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
