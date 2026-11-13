<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('user_roles')) {
            return;
        }

        if (DB::table('roles')->where('name', '!=', 'admin')->exists()) {
            throw new RuntimeException('Existing custom roles require an explicit mapping before removing the legacy access tables.');
        }

        if (! DB::table('users')->exists()) {
            return;
        }

        $email = getenv('KERJAKUY_LEGACY_ADMIN_EMAIL');
        $hasVerifiedAdmin = $email !== false && DB::table('users')
            ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('users.email', $email)
            ->where('users.status', 'active')
            ->where('roles.name', 'admin')
            ->exists();

        if (! $hasVerifiedAdmin) {
            throw new RuntimeException('Set KERJAKUY_LEGACY_ADMIN_EMAIL to an active user assigned the legacy admin role before upgrading.');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
