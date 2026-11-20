<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_family_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->index()->constrained()->restrictOnDelete();
            $table->string('full_name', 150);
            $table->string('relationship', 50);
            $table->date('birth_date')->nullable();
            $table->string('occupation', 100)->nullable();
            $table->string('phone', 30)->nullable();
            $table->boolean('is_tax_dependent')->default(false);
            $table->date('tax_dependent_start_date')->nullable();
            $table->date('tax_dependent_end_date')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE employee_family_members
                ADD CONSTRAINT employee_family_members_tax_period_check CHECK (
                    (is_tax_dependent AND tax_dependent_start_date IS NOT NULL
                        AND (tax_dependent_end_date IS NULL OR tax_dependent_end_date >= tax_dependent_start_date)
                        AND (birth_date IS NULL OR tax_dependent_start_date >= birth_date))
                    OR (NOT is_tax_dependent AND tax_dependent_start_date IS NULL AND tax_dependent_end_date IS NULL)
                )
                SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_family_members');
    }
};
