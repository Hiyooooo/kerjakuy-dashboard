<?php

namespace Tests\Feature\Organization;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\AttendanceLocation;
use App\Organization\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_manage_locations_without_overwriting_a_newer_edit(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $office = Office::create(['code' => 'JKT', 'name' => 'Kantor Jakarta']);
        $index = route('admin.attendance-locations.index');
        $this->get($index)->assertOk()->assertSee('Belum ada lokasi absen');
        $this->get(route('admin.attendance-locations.create'))->assertOk()->assertSee('Kantor Jakarta')->assertSee('Radius (meter)');

        $data = [...AttendanceLocation::factory()->raw(), 'office_id' => (string) $office->id];
        $this->post(route('admin.attendance-locations.store'), $data)->assertRedirect($index);
        $location = AttendanceLocation::sole();
        $this->assertSame($office->id, $location->office_id);
        $this->assertSame('-6.1753924', $location->latitude);
        $this->get($index)->assertOk()->assertSee('Kantor Jakarta')->assertSee('Radius 100 m');

        $edit = route('admin.attendance-locations.edit', $location);
        $update = route('admin.attendance-locations.update', $location);
        $version = $location->editVersion();
        $this->get($edit)->assertOk()->assertSee($version);
        $this->put($update, [
            ...$data, 'office_id' => '', 'radius_meter' => '75', 'is_active' => '0', 'edit_version' => $version,
        ])->assertRedirect($index);
        $this->followingRedirects()->from($edit)->put($update, [...$data, 'edit_version' => $version])
            ->assertOk()->assertSee('Buka versi terbaru');

        $this->assertNull($location->refresh()->office_id);
        $this->assertSame(75, $location->radius_meter);
        $this->assertFalse($location->is_active);
        $this->get($index)->assertOk()->assertSee('Tanpa kantor')->assertSee('Nonaktif');
    }

    public function test_invalid_coordinates_radius_and_office_are_rejected(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'super_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $data = AttendanceLocation::factory()->raw();
        $store = route('admin.attendance-locations.store');
        $this->post($store, [...$data, 'office_id' => '987654'])->assertSessionHasErrors('office_id');
        $this->followingRedirects()->from(route('admin.attendance-locations.create'))
            ->post($store, [...$data, 'latitude' => ['0'], 'longitude' => '180.0000001', 'radius_meter' => '0'])
            ->assertOk()->assertSee('id="latitude-error"', false)
            ->assertSee('id="longitude-error"', false)->assertSee('id="radius_meter-error"', false);

        $this->assertDatabaseCount('attendance_locations', 0);
    }

    public function test_radius_limit_is_enforced_on_create_and_update(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $store = route('admin.attendance-locations.store');
        $data = AttendanceLocation::factory()->raw(['radius_meter' => '1000']);

        $this->get(route('admin.attendance-locations.create'))->assertOk()->assertSee('max="1000"', false);
        $this->post($store, [...$data, 'radius_meter' => '1001'])->assertSessionHasErrors('radius_meter');
        $this->post($store, $data)->assertRedirectToRoute('admin.attendance-locations.index');

        $location = AttendanceLocation::sole();
        $this->put(route('admin.attendance-locations.update', $location), [
            ...$data,
            'radius_meter' => '1001',
            'edit_version' => $location->editVersion(),
        ])->assertSessionHasErrors('radius_meter');
        $this->assertSame(1000, $location->refresh()->radius_meter);
    }

    public function test_attendance_admin_cannot_manage_locations(): void
    {
        $this->get(route('admin.attendance-locations.index'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create()->refresh());
        $this->get(route('admin.attendance-locations.index'))->assertForbidden();
        $this->post(route('admin.attendance-locations.store'))->assertForbidden();
    }
}
