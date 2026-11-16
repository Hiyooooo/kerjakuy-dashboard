<?php

namespace Tests\Feature\Organization;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\AttendanceMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceMethodTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_and_deactivate_an_attendance_method(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();

        $this->get(route('admin.attendance-methods.index'))->assertOk()->assertSee('Belum ada metode absen');
        $this->get(route('admin.attendance-methods.create'))->assertOk()->assertSee('Syarat absen');

        $data = AttendanceMethod::factory()->raw([
            'name' => 'Absen kantor',
            'requires_location' => '1',
            'requires_face_verification' => '1',
            'requires_registered_device' => '0',
        ]);
        $this->post(route('admin.attendance-methods.store'), $data)->assertRedirectToRoute('admin.attendance-methods.index');
        $method = AttendanceMethod::sole();
        $this->assertTrue($method->requires_location);
        $this->assertTrue($method->requires_face_verification);
        $this->assertFalse($method->requires_registered_device);
        $this->get(route('admin.attendance-methods.index'))->assertOk()->assertSee('Lokasi absen, Verifikasi wajah');
        $this->post(route('admin.attendance-methods.store'), [...$data, 'name' => 'ABSEN KANTOR'])->assertSessionHasErrors('name');

        $this->get(route('admin.attendance-methods.edit', $method))->assertOk()->assertDontSee('Hapus metode absen');
        $this->put(route('admin.attendance-methods.update', $method), [
            ...$data,
            'requires_location' => '0',
            'is_active' => '0',
            'edit_version' => $method->editVersion(),
        ])
            ->assertRedirectToRoute('admin.attendance-methods.index');
        $this->assertFalse($method->refresh()->requires_location);
        $this->assertFalse($method->is_active);
        $this->get(route('admin.attendance-methods.index'))->assertOk()->assertSee('Nonaktif');
    }

    public function test_stale_form_cannot_reactivate_a_method_after_another_admin_deactivates_it(): void
    {
        $this->freezeTime();
        $this->withoutVite();
        $hr = User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
        $superAdmin = User::factory()->for(Role::where('code', 'super_admin')->firstOrFail())->create()->refresh();
        $method = AttendanceMethod::factory()->create(['is_active' => true])->refresh();
        $edit = route('admin.attendance-methods.edit', $method);
        $update = route('admin.attendance-methods.update', $method);
        $version = $method->editVersion();
        $data = [...$method->only($method->getFillable()), 'edit_version' => $version];

        $this->actingAs($hr)->get($edit)->assertOk()->assertSee('name="edit_version" type="hidden" value="'.$version.'"', false);
        $this->actingAs($superAdmin)->put($update, [...$data, 'is_active' => false])
            ->assertRedirectToRoute('admin.attendance-methods.index');

        $this->actingAs($hr)->followingRedirects()->from($edit)
            ->put($update, [...$data, 'name' => 'Nama dari form lama'])
            ->assertOk()->assertSee('Buka versi terbaru')
            ->assertSee('name="edit_version" type="hidden" value="'.$version.'"', false);

        $this->assertDatabaseHas('attendance_methods', [
            'id' => $method->id,
            'name' => $method->name,
            'is_active' => false,
        ]);
    }

    public function test_invalid_input_returns_to_the_form_with_field_errors(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'super_admin')->firstOrFail())->create()->refresh())->withoutVite();

        $this->followingRedirects()->from(route('admin.attendance-methods.create'))
            ->post(route('admin.attendance-methods.store'), [
                'name' => ['Absen kantor'],
                'requires_location' => ['1'],
                'is_active' => ['1'],
            ])->assertOk()->assertSee('id="name-error"', false)
            ->assertSee('id="requires_location-error"', false)
            ->assertSee('id="requires_face_verification-error"', false)
            ->assertSee('id="requires_registered_device-error"', false)
            ->assertSee('id="is_active-error"', false);

        $this->assertDatabaseCount('attendance_methods', 0);
    }

    public function test_guests_and_attendance_admins_cannot_manage_attendance_methods(): void
    {
        $this->get(route('admin.attendance-methods.index'))->assertRedirectToRoute('login');

        $this->actingAs(User::factory()->create()->refresh())->withoutVite();
        $this->get(route('admin.attendance-methods.index'))->assertForbidden();
        $this->post(route('admin.attendance-methods.store'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.attendance-methods.index'));
    }
}
