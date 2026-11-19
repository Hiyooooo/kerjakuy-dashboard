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
        Schema::table('shift_swap_request_details', function (Blueprint $table): void {
            $table->json('requester_schedule_snapshot')->nullable();
            $table->json('target_schedule_snapshot')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_swap_request_details', function (Blueprint $table): void {
            $table->dropColumn(['requester_schedule_snapshot', 'target_schedule_snapshot']);
        });
    }
};
