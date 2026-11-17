<?php

namespace App\Access\Http\Requests;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('admin.accounts.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return $this->rulesFor(null);
    }

    /** @return array<string, array<mixed>> */
    protected function rulesFor(?User $account): array
    {
        $uniqueEmail = Rule::unique(User::class);
        $uniqueEmployee = Rule::unique(User::class, 'employee_id');
        if ($account !== null) {
            $uniqueEmail->ignore($account);
            $uniqueEmployee->ignore($account);
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', $uniqueEmail],
            'status' => ['required', Rule::in(array_keys(User::ACCOUNT_STATUSES))],
            'role_id' => ['required', 'integer', Rule::exists(Role::class, 'id')->whereIn('code', Role::ASSIGNABLE)],
            'employee_id' => ['bail', 'nullable', 'integer', Rule::exists(Employee::class, 'id'), $uniqueEmployee],
            ...($account !== null && $this->has('employee_id') ? ['employee_link_version' => ['required', 'string', 'size:64']] : []),
            'password' => [
                'bail', $account === null ? 'required' : 'nullable', 'string', 'min:12', 'confirmed',
                function (string $attribute, string $value, Closure $fail): void {
                    if (strlen($value) > 72 || str_contains($value, "\0")) {
                        $fail('Kata sandi maksimal 72 byte dan tidak boleh berisi karakter NUL.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.exists' => 'Pegawai tidak ditemukan. Pilih pegawai lain.',
            'employee_id.unique' => 'Pegawai ini sudah terhubung ke akun lain.',
            'employee_link_version.*' => 'Buka ulang form pengguna sebelum mengubah tautan pegawai.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw $this->forAccountForm(new ValidationException($validator));
    }

    public function forAccountForm(ValidationException $exception): ValidationException
    {
        if ($this->expectsJson()) {
            return $exception;
        }

        $account = $this->route('user');
        $token = (string) Str::uuid();
        $redirect = $account
            ? to_route('admin.accounts.edit', [$account, 'form' => $token])
            : to_route('admin.accounts.create', ['form' => $token]);

        $exception->response = $redirect->withInput([
            ...$this->only(['name', 'email', 'role_id', 'status', 'employee_id', 'employee_link_version']),
            '_account_form' => 'account_'.($account?->getKey() ?? 'create'),
            '_account_form_token' => $token,
        ])->withErrors($exception->errors());

        return $exception;
    }
}
