<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedule_template_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_schedule_template_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('weekday_number');
            $table->boolean('is_working_day')->default(false);
            $table->foreignId('work_shift_id')->nullable()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->unique(['work_schedule_template_id', 'weekday_number'], 'work_schedule_template_weekday_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE work_schedule_template_days ADD CHECK (weekday_number BETWEEN 1 AND 7), ADD CHECK (is_working_day = (work_shift_id IS NOT NULL))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedule_template_days');
    }
};
