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
        Schema::create('leave_balance_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_leave_entitlement_id')->index()->constrained()->restrictOnDelete();
            $table->decimal('adjustment_days', 5, 2);
            $table->text('reason');
            $table->foreignId('adjusted_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('adjusted_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balance_adjustments');
    }
};
