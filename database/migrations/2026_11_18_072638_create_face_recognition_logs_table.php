<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_recognition_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('employee_face_profile_id')->nullable()->constrained()->restrictOnDelete();
            $table->enum('verification_type', ['check_in', 'check_out']);
            $table->string('captured_photo_path')->nullable();
            $table->decimal('match_score', 5, 2)->nullable();
            $table->decimal('minimum_match_score', 5, 2)->nullable();
            $table->enum('status', ['passed', 'failed']);
            $table->string('failure_reason')->nullable();
            $table->timestamp('captured_at');
            $table->timestamp('created_at');
            $table->index(['captured_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_recognition_logs');
    }
};
