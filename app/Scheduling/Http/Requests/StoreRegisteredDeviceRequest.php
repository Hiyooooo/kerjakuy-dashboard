<?php

namespace App\Scheduling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegisteredDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.devices.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        $uuid = $this->input('device_uuid');
        if (is_string($uuid)) {
            $this->merge(['device_uuid' => strtolower(trim($uuid))]);
        }
    }

    public function rules(): array
    {
        return [
            'device_uuid' => ['bail', 'required', 'uuid'],
            'device_name' => ['nullable', 'string', 'max:150'],
            'platform' => ['nullable', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function attributes(): array
    {
        return [
            'device_uuid' => 'UUID perangkat',
            'device_name' => 'nama perangkat',
            'platform' => 'platform',
            'app_version' => 'versi aplikasi',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.registered-devices.index');
    }
}
