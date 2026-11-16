<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_tolerance_policies', function (Blueprint $table): void {
            $table->id();
            $table->decimal('minimum_match_score', 5, 2);
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE face_tolerance_policies
                ADD CONSTRAINT face_tolerance_score_range CHECK (minimum_match_score BETWEEN 0 AND 100),
                ADD CONSTRAINT face_tolerance_date_order CHECK (effective_end_date >= effective_start_date),
                ADD CONSTRAINT face_tolerance_period_exclusion
                    EXCLUDE USING gist (daterange(effective_start_date, effective_end_date, '[]') WITH &&)
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('face_tolerance_policies');
    }
};
