<?php

namespace Database\Seeders;

use App\Access\Models\Role;
use App\Access\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AdminAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (['super_admin' => 'Super Admin', 'hrd_admin' => 'HRD Admin', 'attendance_admin' => 'Security/Attendance Admin'] as $code => $name) {
                Role::updateOrCreate(['code' => $code], ['name' => $name]);
            }

            $email = config('admin.initial_email');
            $password = config('admin.initial_password');

            if (blank($email) && blank($password)) {
                return;
            }

            $validator = Validator::make(['email' => $email, 'password' => $password], [
                'email' => ['required', 'string', 'email', 'max:150'],
                'password' => ['required', 'string', 'min:12'],
            ]);

            if ($validator->fails() || strlen($password) > 72 || str_contains($password, "\0")) {
                throw new RuntimeException('Set a valid KERJAKUY_ADMIN_EMAIL and KERJAKUY_ADMIN_PASSWORD (at least 12 characters and at most 72 bytes, without NUL).');
            }

            $role = Role::where('code', 'super_admin')->firstOrFail();
            $account = User::firstOrCreate(
                ['email' => $email],
                ['name' => 'Super Admin', 'password' => Hash::make($password), 'role_id' => $role->id, 'status' => 'active'],
            );

            if (! $account->isSuperAdmin() || $account->status !== 'active') {
                throw new RuntimeException('The configured email already belongs to an account that is not an active Super Admin.');
            }
        });
    }
}
