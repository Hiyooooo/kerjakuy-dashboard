<?php

namespace Tests\Feature\Scheduling;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Scheduling\Models\EmployeeDeviceLock;
use App\Scheduling\Models\RegisteredDevice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeDeviceLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_lock_release_and_reassign_a_device_without_overwriting_history(): void
    {
        $admin = User::factory()->for(Role::where('code', 'attendance_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $employee = Employee::factory()->create(['employee_code' => 'EMP-001']);
        $other = Employee::factory()->create();
        $device = RegisteredDevice::factory()->create();
        $alternative = RegisteredDevice::factory()->create();
        $index = route('admin.registered-devices.locks.index', $device);
        $store = route('admin.registered-devices.locks.store', $device);

        $this->get($index)->assertOk()->assertSee('Belum ada riwayat penguncian.');
        $this->post($store, ['employee_code' => 'emp-001'])->assertRedirect($index)->assertSessionHasNoErrors();
        $lock = $device->deviceLocks()->sole();
        $this->assertSame($admin->id, $lock->locked_by_user_id);
        $this->assertNotNull($lock->locked_at);
        $this->post($store, ['employee_code' => $other->employee_code])->assertSessionHasErrors('employee_code');
        $this->post(route('admin.registered-devices.locks.store', $alternative), ['employee_code' => $employee->employee_code])
            ->assertSessionHasErrors('employee_code');
        $this->patch(route('admin.registered-devices.locks.release', [$alternative, $lock]))->assertNotFound();

        $release = route('admin.registered-devices.locks.release', [$device, $lock]);
        $this->patch($release)->assertRedirect($index);
        $lock->refresh();
        $this->assertSame($admin->id, $lock->released_by_user_id);
        $this->assertNotNull($lock->released_at);
        $this->post($store, ['employee_code' => $other->employee_code])->assertSessionHasNoErrors();
        $this->patch($release)->assertRedirect($index)->assertSessionHas('device_status', 'Penguncian ini sudah dilepas.');
        $this->assertEquals($lock->released_at, $lock->fresh()->released_at);
        $this->assertSame($other->id, $device->deviceLocks()->whereNull('released_at')->sole()->employee_id);
        $this->get($index)->assertOk()->assertSee('Riwayat penguncian')->assertSee('Dilepas');
    }

    public function test_blocked_devices_and_inactive_admins_cannot_create_locks(): void
    {
        $device = RegisteredDevice::factory()->create(['status' => 'blocked']);
        $employee = Employee::factory()->create();
        $store = route('admin.registered-devices.locks.store', $device);
        $data = ['employee_code' => $employee->employee_code];

        $this->actingAs(User::factory()->create(['status' => 'inactive'])->refresh());
        $this->post($store, $data)->assertForbidden();
        $this->actingAs(User::factory()->create()->refresh());
        $this->post($store, $data)->assertSessionHasErrors(['employee_code' => 'Perangkat yang diblokir tidak bisa dikunci.']);
        $this->assertFalse($device->deviceLocks()->exists());
    }

    public function test_database_enforces_both_active_lock_constraints(): void
    {
        $active = EmployeeDeviceLock::factory()->create();
        foreach ([
            ['employee_id' => $active->employee_id],
            ['registered_device_id' => $active->registered_device_id],
        ] as $attributes) {
            try {
                DB::transaction(fn () => EmployeeDeviceLock::factory()->create($attributes));
                $this->fail('Database accepted a duplicate active lock.');
            } catch (UniqueConstraintViolationException) {
                $this->assertDatabaseCount('employee_device_locks', 1);
            }
        }
    }
}
