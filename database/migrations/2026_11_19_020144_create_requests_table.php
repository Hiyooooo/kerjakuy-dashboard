<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_number', 40)->unique();
            $table->foreignId('employee_id')->index()->constrained()->restrictOnDelete();
            $table->enum('request_type', ['leave', 'late', 'overtime', 'shift_swap']);
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->timestamp('submitted_at');
            $table->foreignId('reviewed_by_user_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->text('reviewer_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
