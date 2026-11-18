<?php

namespace App\Scheduling\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Scheduling\Http\Requests\StoreRegisteredDeviceRequest;
use App\Scheduling\Models\RegisteredDevice;
use App\Scheduling\Services\RegisteredDeviceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class RegisteredDeviceController extends Controller
{
    public function index(): View
    {
        return view('admin.scheduling.registered-devices', [
            'devices' => RegisteredDevice::query()->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(StoreRegisteredDeviceRequest $request, RegisteredDeviceService $devices): RedirectResponse
    {
        try {
            $devices->register($request->validated(), $request->user());
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('admin.registered-devices.index'));
        }

        return to_route('admin.registered-devices.index')->with('device_status', 'Perangkat berhasil didaftarkan.');
    }

    public function block(RegisteredDevice $registeredDevice, RegisteredDeviceService $devices): RedirectResponse
    {
        $devices->block($registeredDevice);

        return to_route('admin.registered-devices.index')->with('device_status', 'Perangkat diblokir.');
    }
}
