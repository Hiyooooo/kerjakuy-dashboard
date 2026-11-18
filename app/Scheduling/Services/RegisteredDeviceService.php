<?php

namespace App\Scheduling\Services;

use App\Access\Models\User;
use App\Scheduling\Models\RegisteredDevice;
use Illuminate\Validation\ValidationException;

class RegisteredDeviceService
{
    /** @param array{device_uuid: string, device_name?: ?string, platform?: ?string, app_version?: ?string} $data */
    public function register(array $data, User $actor): void
    {
        $device = RegisteredDevice::query()->createOrFirst(['device_uuid' => $data['device_uuid']], [
            ...$data,
            'registered_by_user_id' => $actor->id,
            'registered_by_name' => $actor->name,
        ]);

        if (! $device->wasRecentlyCreated) {
            throw ValidationException::withMessages(['device_uuid' => 'UUID ini sudah terdaftar.']);
        }
    }

    public function block(RegisteredDevice $device): void
    {
        RegisteredDevice::query()->whereKey($device->getKey())->where('status', 'registered')->update(['status' => 'blocked']);
    }
}
