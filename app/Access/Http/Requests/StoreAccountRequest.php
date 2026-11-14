<?php

namespace App\Access\Http\Requests;

use App\Access\Models\Role;
use App\Access\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        if ($account !== null) {
            $uniqueEmail->ignore($account);
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', $uniqueEmail],
            'status' => ['required', Rule::in(array_keys(User::ACCOUNT_STATUSES))],
            'role_id' => ['required', 'integer', Rule::exists(Role::class, 'id')->whereIn('code', Role::ASSIGNABLE)],
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
}
