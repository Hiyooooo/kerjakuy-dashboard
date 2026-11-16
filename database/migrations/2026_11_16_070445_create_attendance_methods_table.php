<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendance_methods', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->boolean('requires_location')->default(false);
            $table->boolean('requires_face_verification')->default(false);
            $table->boolean('requires_registered_device')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX attendance_methods_name_unique ON attendance_methods (LOWER(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_methods');
    }
};
