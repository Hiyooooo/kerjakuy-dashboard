<?php

namespace App\Scheduling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreEmployeeDeviceLockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.devices.manage') === true;
    }

    public function rules(): array
    {
        return ['employee_code' => ['required', 'string', 'max:30']];
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('employee_code');
        if (is_string($code)) {
            $this->merge(['employee_code' => Str::upper($code)]);
        }
    }

    public function attributes(): array
    {
        return ['employee_code' => 'kode pegawai'];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.registered-devices.locks.index', $this->route('registeredDevice'));
    }
}
