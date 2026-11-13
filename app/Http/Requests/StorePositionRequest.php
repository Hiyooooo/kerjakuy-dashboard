<?php

namespace App\Http\Requests;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        $record = $this->route('position');
        $uniqueCode = Rule::unique(Position::class, 'code');

        if ($record instanceof Position) {
            $uniqueCode->ignore($record);
        }

        return [
            'code' => ['required', 'string', 'max:30', $uniqueCode],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
