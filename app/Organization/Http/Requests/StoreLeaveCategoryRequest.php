<?php

namespace App\Organization\Http\Requests;

use App\Organization\Models\LeaveCategory;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:100', function (string $attribute, string $value, Closure $fail): void {
                $record = $this->route('leave_category');
                $query = LeaveCategory::matchingName($value);

                if ($record instanceof LeaveCategory) {
                    $query->whereKeyNot($record);
                }

                if ($query->exists()) {
                    $fail(LeaveCategory::DUPLICATE_MESSAGE);
                }
            }],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
