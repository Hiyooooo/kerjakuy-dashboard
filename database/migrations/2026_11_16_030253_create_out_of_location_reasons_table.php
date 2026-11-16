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
        Schema::create('out_of_location_reasons', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->boolean('requires_note')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement('CREATE UNIQUE INDEX out_of_location_reasons_name_unique ON out_of_location_reasons (LOWER(name))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('out_of_location_reasons');
    }
};
