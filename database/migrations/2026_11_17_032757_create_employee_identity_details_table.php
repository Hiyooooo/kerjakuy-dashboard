<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_identity_details', function (Blueprint $table): void {
            $table->foreignId('employee_id')->primary()->constrained()->restrictOnDelete();
            $table->string('gender', 20);
            $table->text('national_id_ciphertext');
            $table->string('national_id_hash', 64)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_identity_details');
    }
};
