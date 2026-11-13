<?php

namespace App\Http\Requests;

use App\Models\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('admin.permissions.manage');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        $permission = $this->route('permission');
        $uniqueName = Rule::unique(Permission::class);
        if ($permission instanceof Permission) {
            $uniqueName->ignore($permission);
        }

        return [
            'name' => ['required', 'string', 'max:100', 'regex:/\A[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)+\z/', Rule::notIn(Permission::BUILT_IN), $uniqueName],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
