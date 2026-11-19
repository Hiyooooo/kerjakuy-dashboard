<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_request_details', function (Blueprint $table): void {
            $table->foreignId('request_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_record_id')->nullable()->index()->constrained()->restrictOnDelete();
            $table->date('late_date');
            $table->time('expected_check_in')->nullable();
            $table->time('actual_check_in')->nullable();
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_request_details');
    }
};
