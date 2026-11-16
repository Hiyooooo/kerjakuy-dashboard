<?php

namespace Tests\Feature\Organization;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\OutOfLocationReason;
use App\Organization\Services\OutOfLocationReasonService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OutOfLocationReasonTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_manage_reasons_and_set_whether_a_note_is_required(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $this->get(route('admin.out-of-location-reasons.index'))->assertOk()->assertSee('Belum ada alasan di luar lokasi');
        $this->get(route('admin.out-of-location-reasons.create'))->assertOk()->assertSee('Catatan pegawai');

        $data = OutOfLocationReason::factory()->raw(['name' => 'Tugas lapangan']);
        $this->post(route('admin.out-of-location-reasons.store'), $data)
            ->assertRedirectToRoute('admin.out-of-location-reasons.index');
        $reason = OutOfLocationReason::sole();
        $this->get(route('admin.out-of-location-reasons.index'))->assertOk()->assertSee('Catatan wajib');

        $duplicate = [...$data, 'name' => 'TUGAS LAPANGAN'];
        $this->post(route('admin.out-of-location-reasons.store'), $duplicate)->assertSessionHasErrors('name');
        $this->assertThrows(
            fn () => app(OutOfLocationReasonService::class)->create($duplicate),
            fn (ValidationException $exception): bool => isset($exception->errors()['name']),
        );

        $this->get(route('admin.out-of-location-reasons.edit', $reason))->assertOk()->assertSee('Tugas lapangan');
        $this->put(route('admin.out-of-location-reasons.update', $reason), [...$duplicate, 'requires_note' => false, 'is_active' => false])
            ->assertRedirectToRoute('admin.out-of-location-reasons.index');
        $this->get(route('admin.out-of-location-reasons.index'))->assertOk()
            ->assertSee('Catatan opsional')->assertSee('Nonaktif');
        $this->delete(route('admin.out-of-location-reasons.destroy', $reason))->assertRedirectToRoute('admin.out-of-location-reasons.index');
        $this->assertModelMissing($reason);
    }

    public function test_invalid_field_types_render_validation_errors_on_the_form(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $form = route('admin.out-of-location-reasons.create');

        $this->followingRedirects()->from($form)->post(route('admin.out-of-location-reasons.store'), [
            'name' => ['Tugas lapangan'],
            'requires_note' => ['1'],
            'is_active' => ['1'],
        ])->assertOk()->assertSee('id="name-error"', false)
            ->assertSee('id="requires-note-error"', false)->assertSee('id="is_active-error"', false);
    }

    public function test_reasons_with_references_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $reason = OutOfLocationReason::factory()->create();
        Schema::create('out_of_location_reason_test_references', function (Blueprint $table): void {
            $table->foreignId('out_of_location_reason_id')->constrained()->restrictOnDelete();
        });
        DB::table('out_of_location_reason_test_references')->insert(['out_of_location_reason_id' => $reason->id]);

        $this->from(route('admin.out-of-location-reasons.edit', $reason))->delete(route('admin.out-of-location-reasons.destroy', $reason))
            ->assertSessionHasErrors('delete');
        $this->assertModelExists($reason);
    }

    public function test_guest_and_attendance_admin_cannot_manage_reasons(): void
    {
        $this->get(route('admin.out-of-location-reasons.index'))->assertRedirectToRoute('login');
        $reason = OutOfLocationReason::factory()->create();

        $this->actingAs(User::factory()->create()->refresh())->withoutVite();
        $this->post(route('admin.out-of-location-reasons.store'))->assertForbidden();
        $this->delete(route('admin.out-of-location-reasons.destroy', $reason))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.out-of-location-reasons.index'));
    }
}
