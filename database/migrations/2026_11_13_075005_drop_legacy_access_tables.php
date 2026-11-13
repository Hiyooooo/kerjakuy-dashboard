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
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('permissions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('role_id')->constrained('roles');
            $table->timestamp('created_at')->nullable();
            $table->primary(['user_id', 'role_id']);
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('permission_id')->constrained('permissions');
            $table->timestamp('created_at')->nullable();
            $table->primary(['role_id', 'permission_id']);
        });
    }
};
