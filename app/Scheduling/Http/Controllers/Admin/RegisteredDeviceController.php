<?php

namespace App\Scheduling\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Scheduling\Http\Requests\StoreRegisteredDeviceRequest;
use App\Scheduling\Models\RegisteredDevice;
use App\Scheduling\Services\RegisteredDeviceService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RegisteredDeviceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:registered,blocked'],
        ]);
        $devices = RegisteredDevice::with('activeLock.employee:id,full_name,employee_code')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        if (($filters['search'] ?? '') !== '') {
            $pattern = '%'.strtr($filters['search'], ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $devices->where(fn ($query) => $query
                ->whereRaw("LOWER(device_name) LIKE LOWER(?) ESCAPE '!'", [$pattern])
                ->orWhereRaw("LOWER(device_uuid) LIKE LOWER(?) ESCAPE '!'", [$pattern]));
        }

        return view('admin.scheduling.registered-devices', [
            'devices' => $devices->orderByDesc('id')->paginate(15)->appends($filters),
            'filters' => $filters,
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
