<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_entitlements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->foreignId('leave_category_id')->constrained()->restrictOnDelete();
            $table->integer('entitlement_year');
            $table->decimal('granted_days', 5, 2)->default(0);
            $table->decimal('carry_over_days', 5, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id', 'leave_category_id', 'entitlement_year'], 'leave_entitlement_employee_category_year_unique');
        });

        Schema::create('leave_request_details', function (Blueprint $table): void {
            $table->foreignId('request_id')->primary()->constrained()->cascadeOnDelete();
            $table->foreignId('leave_category_id')->constrained()->restrictOnDelete();
            $table->text('reason')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_request_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('leave_request_detail_id')->constrained('leave_request_details', 'request_id')->cascadeOnDelete();
            $table->foreignId('employee_leave_entitlement_id')->constrained()->restrictOnDelete();
            $table->date('leave_date');
            $table->decimal('duration_days', 3, 2)->default(1);
            $table->timestamps();
            $table->unique(['leave_request_detail_id', 'leave_date']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leave_request_days ADD CHECK (duration_days > 0 AND duration_days <= 1)');
            DB::statement('ALTER TABLE employee_leave_entitlements ADD CHECK (granted_days >= 0 AND carry_over_days >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_days');
        Schema::dropIfExists('leave_request_details');
        Schema::dropIfExists('employee_leave_entitlements');
    }
};
