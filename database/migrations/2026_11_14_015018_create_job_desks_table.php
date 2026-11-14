<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_desks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->string('title', 150);
            $table->text('job_description')->nullable();
            $table->text('job_specification')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['position_id', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_desks');
    }
};
