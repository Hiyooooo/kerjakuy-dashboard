<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registered_devices', function (Blueprint $table): void {
            $table->id();
            $table->string('device_uuid', 150)->unique();
            $table->string('device_name', 150)->nullable();
            $table->string('platform', 50)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->enum('status', ['registered', 'blocked'])->default('registered');
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registered_devices');
    }
};
