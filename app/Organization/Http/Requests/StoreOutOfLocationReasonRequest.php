<?php

namespace App\Organization\Http\Requests;

use App\Organization\Models\OutOfLocationReason;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreOutOfLocationReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:120', function (string $attribute, string $value, Closure $fail): void {
                $record = $this->route('out_of_location_reason');
                $query = OutOfLocationReason::matchingName($value);

                if ($record instanceof OutOfLocationReason) {
                    $query->whereKeyNot($record);
                }

                if ($query->exists()) {
                    $fail(OutOfLocationReason::DUPLICATE_MESSAGE);
                }
            }],
            'requires_note' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
