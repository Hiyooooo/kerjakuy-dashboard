<?php

namespace App\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        return ['year' => ['bail', 'required', 'integer', 'between:1000,9999']];
    }
}
