<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_educations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->index()->constrained()->restrictOnDelete();
            $table->string('institution_name', 180);
            $table->string('education_level', 50);
            $table->string('major', 100)->nullable();
            $table->integer('start_year')->nullable();
            $table->integer('graduation_year')->nullable();
            $table->string('certificate_number', 100)->nullable();
            $table->timestamps();
        });

        Schema::create('employee_trainings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->index()->constrained()->restrictOnDelete();
            $table->string('title', 180);
            $table->string('provider', 150)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('certificate_number', 100)->nullable();
            $table->string('certificate_file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_coachings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->index()->constrained()->restrictOnDelete();
            $table->date('coaching_date');
            $table->string('subject', 180);
            $table->text('description')->nullable();
            $table->text('follow_up')->nullable();
            $table->foreignId('created_by_user_id')->index()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE employee_educations ADD CONSTRAINT employee_educations_years_check
                CHECK ((start_year IS NULL OR start_year BETWEEN 1 AND 9999)
                    AND (graduation_year IS NULL OR graduation_year BETWEEN 1 AND 9999)
                    AND (start_year IS NULL OR graduation_year IS NULL OR graduation_year >= start_year))');
            DB::statement('ALTER TABLE employee_trainings ADD CONSTRAINT employee_trainings_dates_check
                CHECK (start_date IS NULL OR end_date IS NULL OR end_date >= start_date)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_coachings');
        Schema::dropIfExists('employee_trainings');
        Schema::dropIfExists('employee_educations');
    }
};
