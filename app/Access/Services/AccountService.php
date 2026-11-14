<?php

namespace App\Access\Services;

use App\Access\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return User::with('role')->orderByDesc('id')->paginate($perPage);
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    public function update(array $data, User $user): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($user->isDirty(['email', 'password', 'status', 'role_id'])) {
            $user->setRememberToken(null);
        }

        $revokeSessions = $user->isDirty('password') && config('session.driver') === 'database';

        DB::transaction(function () use ($user, $revokeSessions): void {
            $user->save();

            if ($revokeSessions) {
                DB::connection(config('session.connection'))->table(config('session.table'))
                    ->where('user_id', $user->getKey())
                    ->delete();
            }
        });

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
