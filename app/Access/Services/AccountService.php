<?php

namespace App\Access\Services;

use App\Access\Models\User;
use App\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountService
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return User::with(['role', 'employee:id,employee_code,full_name'])->orderByDesc('id')->paginate($perPage);
    }

    /** @return Collection<int, Employee> */
    public function linkableEmployees(User $account): Collection
    {
        return Employee::whereDoesntHave('user')
            ->when($account->employee_id !== null, fn ($query) => $query->orWhereKey($account->employee_id))
            ->orderBy('full_name')->orderBy('id')->get(['id', 'employee_code', 'full_name']);
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return DB::transaction(function () use ($data): User {
            $this->checkEmployee($data['employee_id'] ?? null, new User);

            return User::create($data);
        });
    }

    public function update(array $data, User $user, ?string $employeeLinkVersion = null): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        return DB::transaction(function () use ($data, $user, $employeeLinkVersion): User {
            $user = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (array_key_exists('employee_id', $data)) {
                if (! hash_equals($user->employeeLinkVersion(), $employeeLinkVersion ?? '')) {
                    throw ValidationException::withMessages(['employee_link_version' => 'Tautan pegawai sudah berubah. Buka versi terbaru sebelum menyimpan kembali.']);
                }

                $this->checkEmployee($data['employee_id'], $user);
            }

            $user->fill($data);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            if ($user->isDirty(['email', 'password', 'status', 'role_id', 'employee_id'])) {
                $user->setRememberToken(null);
            }

            $revokeSessions = $user->isDirty(['password', 'employee_id']) && config('session.driver') === 'database';
            $user->save();

            if ($revokeSessions) {
                DB::connection(config('session.connection'))->table(config('session.table'))
                    ->where('user_id', $user->getKey())
                    ->delete();
            }

            return $user;
        });
    }

    private function checkEmployee(int|string|null $employeeId, User $account): void
    {
        if ($employeeId === null) {
            return;
        }

        if (Employee::query()->lockForUpdate()->find($employeeId) === null) {
            throw ValidationException::withMessages(['employee_id' => 'Pegawai tidak ditemukan. Pilih pegawai lain.']);
        }

        if (User::where('employee_id', $employeeId)->when($account->exists, fn ($query) => $query->whereKeyNot($account))->exists()) {
            throw ValidationException::withMessages(['employee_id' => 'Pegawai ini sudah terhubung ke akun lain.']);
        }
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
