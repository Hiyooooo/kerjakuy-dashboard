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
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('name', 100)->change();
            $table->string('code', 50)->nullable()->unique();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->constrained('roles');
        });

        $legacyAdmin = DB::table('roles')->where('name', 'admin')->first();
        if ($legacyAdmin !== null) {
            DB::table('roles')->where('id', $legacyAdmin->id)->update([
                'code' => 'super_admin',
                'name' => 'Super Admin',
            ]);
        }

        foreach (['super_admin' => 'Super Admin', 'hrd_admin' => 'HRD Admin', 'attendance_admin' => 'Security/Attendance Admin'] as $code => $name) {
            DB::table('roles')->updateOrInsert(['code' => $code], ['name' => $name]);
        }

        $adminEmail = getenv('KERJAKUY_LEGACY_ADMIN_EMAIL');
        if (DB::table('users')->exists()) {
            if ($adminEmail === false || ! DB::table('users')->where('email', $adminEmail)->where('status', 'active')->exists()) {
                throw new RuntimeException('Set KERJAKUY_LEGACY_ADMIN_EMAIL to the verified active legacy administrator.');
            }

            DB::table('users')->where('email', $adminEmail)->update([
                'role_id' => DB::table('roles')->where('code', 'super_admin')->value('id'),
            ]);
            DB::table('users')->whereNull('role_id')->update([
                'role_id' => DB::table('roles')->where('code', 'attendance_admin')->value('id'),
                'status' => 'inactive',
                'remember_token' => null,
            ]);
        }

        if (DB::table('roles')->whereNull('code')->exists()) {
            throw new RuntimeException('Existing custom roles require an explicit mapping.');
        }

        Schema::table('roles', function (Blueprint $table): void {
            $table->string('code', 50)->change();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
        });
        DB::table('roles')->where('code', 'super_admin')->update(['name' => 'admin']);
        DB::table('roles')->whereIn('code', ['hrd_admin', 'attendance_admin'])->delete();
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('code');
            $table->string('name', 80)->change();
        });
    }
};
