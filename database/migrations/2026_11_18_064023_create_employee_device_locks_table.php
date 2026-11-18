<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_device_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('registered_device_id')->constrained()->restrictOnDelete();
            $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at');
            $table->foreignId('released_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['registered_device_id', 'id']);
        });

        DB::statement('CREATE UNIQUE INDEX employee_device_locks_active_employee ON employee_device_locks (employee_id) WHERE released_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX employee_device_locks_active_device ON employee_device_locks (registered_device_id) WHERE released_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_device_locks');
    }
};
