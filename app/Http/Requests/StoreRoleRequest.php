<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('admin.roles.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        $uniqueName = Rule::unique(Role::class);

        if ($role instanceof Role) {
            $uniqueName->ignore($role);
        }

        return [
            'name' => ['required', 'string', 'max:80', 'not_regex:/^admin$/i', $uniqueName],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
