<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('employee_device_locks', function (Blueprint $table): void {
            $table->string('locked_by_name')->nullable();
            $table->string('released_by_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_device_locks', function (Blueprint $table): void {
            $table->dropColumn(['locked_by_name', 'released_by_name']);
        });
    }
};
