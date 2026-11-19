<?php

namespace Tests\Feature\Scheduling;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeEmploymentPeriod;
use App\Scheduling\Models\EmployeeDeviceLock;
use App\Scheduling\Models\RegisteredDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisteredDeviceTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_filters_and_lock_employee_search(): void
    {
        $this->actingAs(User::factory()->create()->refresh())->withoutVite();
        $device = RegisteredDevice::factory()->create(['device_name' => 'Terminal Utama']);
        RegisteredDevice::factory()->create(['device_name' => 'Terminal Cadangan', 'status' => 'blocked']);
        $locked = EmployeeDeviceLock::factory()->create(['registered_device_id' => $device->id]);
        $candidate = Employee::factory()->create(['full_name' => 'Sari Contoh', 'employee_code' => 'PGW-SARI']);
        foreach ([$locked->employee_id, $candidate->id] as $employeeId) {
            EmployeeEmploymentPeriod::factory()->create(['employee_id' => $employeeId]);
        }
        $index = route('admin.registered-devices.index');
        $page = $this->get($index.'?search=terminal&status=registered')->assertOk()->viewData('devices');
        $this->assertSame([$device->id], $page->modelKeys());
        $this->assertTrue($page->first()->activeLock->relationLoaded('employee'));
        $this->assertSame($locked->employee_id, $page->first()->activeLock->employee->id);
        $this->assertStringContainsString('status=registered', $page->url(2));
        $this->assertSame([$device->id], $this->get($index.'?search='.$device->device_uuid)->assertOk()->viewData('devices')->modelKeys());

        $available = RegisteredDevice::factory()->create();
        $lookup = route('admin.registered-devices.locks.employees', $available);
        $this->getJson($lookup.'?search=sari')->assertOk()->assertJsonPath('data.0.employee_code', $candidate->employee_code);
        $this->getJson($lookup.'?search=PGW-SARI')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson($lookup.'?search='.$locked->employee->employee_code)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_can_register_a_device_but_cannot_overwrite_an_existing_uuid(): void
    {
        $admin = User::factory()->for(Role::where('code', 'attendance_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $index = route('admin.registered-devices.index');
        $store = route('admin.registered-devices.store');
        $uuid = '97fbffda-cfe8-4c30-a7c0-c6f21c3bcd9d';

        $this->get($index)->assertOk()->assertSee('Belum ada perangkat terdaftar.');
        $this->post($store, ['device_uuid' => 'bukan-uuid'])->assertRedirect($index)->assertSessionHasErrors('device_uuid');
        $this->post($store, [
            'device_uuid' => strtoupper($uuid),
            'device_name' => 'Ponsel absensi',
            'platform' => 'Android',
            'app_version' => '1.0.0',
            'status' => 'blocked',
        ])->assertRedirect($index)->assertSessionHasNoErrors();

        $device = RegisteredDevice::query()->sole();
        $this->assertSame($uuid, $device->device_uuid);
        $this->assertSame('registered', $device->status);
        $this->assertSame($admin->id, $device->registered_by_user_id);
        $this->assertSame($admin->name, $device->registered_by_name);
        $this->assertNull($device->last_used_at);
        $this->get($index)->assertOk()->assertSee('Ponsel absensi')->assertSee('Versi 1.0.0');

        $this->post($store, ['device_uuid' => $uuid, 'device_name' => 'Nama baru'])
            ->assertRedirect($index)->assertSessionHasErrors(['device_uuid' => 'UUID ini sudah terdaftar.']);
        $this->assertSame('Ponsel absensi', RegisteredDevice::query()->sole()->device_name);

        $block = route('admin.registered-devices.block', $device);
        $this->patch($block)->assertRedirect($index);
        $this->patch($block)->assertRedirect($index);
        $this->get($index)->assertOk()->assertSee('Diblokir')->assertSee('Didaftarkan oleh '.$admin->name);
        $this->assertSame('blocked', $device->refresh()->status);

        $admin->delete();
        $this->assertNull($device->refresh()->registered_by_user_id);
        $this->assertSame($admin->name, $device->registered_by_name);
    }

    public function test_guests_and_inactive_admins_cannot_register_devices(): void
    {
        $index = route('admin.registered-devices.index');
        $store = route('admin.registered-devices.store');
        $this->get($index)->assertRedirect(route('login'));
        $this->post($store)->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(['status' => 'inactive'])->refresh());
        $this->get($index)->assertForbidden();
        $this->post($store)->assertForbidden();
        $device = RegisteredDevice::factory()->create();
        $this->patch(route('admin.registered-devices.block', $device))->assertForbidden();
        $this->assertSame('registered', $device->refresh()->status);
    }
}
