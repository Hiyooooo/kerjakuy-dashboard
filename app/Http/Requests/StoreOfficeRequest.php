<?php

namespace App\Http\Requests;

use App\Models\Office;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        $record = $this->route('office');
        $uniqueCode = Rule::unique(Office::class, 'code');

        if ($record instanceof Office) {
            $uniqueCode->ignore($record);
        }

        return [
            'code' => ['required', 'string', 'max:30', $uniqueCode],
            'name' => ['required', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90', 'decimal:0,7'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180', 'decimal:0,7'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
