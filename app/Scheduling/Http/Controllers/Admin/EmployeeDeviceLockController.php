<?php

namespace App\Scheduling\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Scheduling\Http\Requests\StoreEmployeeDeviceLockRequest;
use App\Scheduling\Models\EmployeeDeviceLock;
use App\Scheduling\Models\RegisteredDevice;
use App\Scheduling\Services\EmployeeDeviceLockService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployeeDeviceLockController extends Controller
{
    public function index(RegisteredDevice $registeredDevice): View
    {
        return view('admin.scheduling.employee-device-locks', [
            'device' => $registeredDevice,
            'activeLock' => $registeredDevice->deviceLocks()->whereNull('released_at')->with('employee')->first(),
            'locks' => $registeredDevice->deviceLocks()->with(['employee', 'lockedBy', 'releasedBy'])->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(StoreEmployeeDeviceLockRequest $request, RegisteredDevice $registeredDevice, EmployeeDeviceLockService $service): RedirectResponse
    {
        try {
            $service->lock($registeredDevice, $request->validated('employee_code'), $request->user()->id);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo(route('admin.registered-devices.locks.index', $registeredDevice));
        }

        return to_route('admin.registered-devices.locks.index', $registeredDevice)->with('device_status', 'Perangkat berhasil dikunci.');
    }

    public function release(Request $request, RegisteredDevice $registeredDevice, EmployeeDeviceLock $deviceLock, EmployeeDeviceLockService $service): RedirectResponse
    {
        $released = $service->release($deviceLock, $request->user()->id);

        return to_route('admin.registered-devices.locks.index', $registeredDevice)
            ->with('device_status', $released ? 'Penguncian perangkat dilepas.' : 'Penguncian ini sudah dilepas.');
    }
}
