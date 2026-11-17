<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Organization\Models\Office;
use App\Organization\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeAssignmentAndFaceProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_assignments_can_be_updated_and_periods_cannot_overlap(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $office = Office::create(['name' => 'Kantor Utama', 'code' => 'UTAMA']);
        $position = Position::create(['name' => 'Analis', 'code' => 'ANALIS']);
        $index = route('admin.employees.assignments.index', $employee);
        $create = route('admin.employees.assignments.create', $employee);
        $store = route('admin.employees.assignments.store', $employee);
        $data = [
            'office_id' => (string) $office->id,
            'position_id' => (string) $position->id,
            'change_type' => 'Penempatan awal',
            'effective_start_date' => '2027-01-01',
        ];

        $this->get($create)->assertOk()->assertSee('Kantor Utama')->assertSee('Analis');
        $this->post($store, $data)->assertRedirect($index);
        $assignment = $employee->assignments()->sole();
        $this->get($index)->assertOk()->assertSee('Analis')->assertSee('Kantor Utama');
        $this->get(route('admin.employees.assignments.edit', [$employee, $assignment]))->assertOk()->assertSee($assignment->editVersion());
        $this->get(route('admin.employees.assignments.edit', [$other, $assignment]))->assertNotFound();
        $this->post($store, [...$data, 'effective_start_date' => '2027-02-01'])
            ->assertSessionHasErrors('effective_start_date', null, $create);
        $this->put(route('admin.employees.assignments.update', [$employee, $assignment]), [
            ...$data, 'effective_end_date' => '2027-01-31', 'edit_version' => $assignment->editVersion(),
        ])->assertRedirect($index);
        $this->post($store, [...$data, 'effective_start_date' => '2027-02-01'])->assertRedirect($index);
    }

    public function test_face_photos_are_private_and_scoped_to_the_employee(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $index = route('admin.employees.face-profiles.index', $employee);
        $store = route('admin.employees.face-profiles.store', $employee);

        $this->get($index)->assertOk()->assertSee('Belum ada foto referensi');
        $this->post($store, ['photo' => UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('photo', null, $index);
        $this->post($store, ['photo' => UploadedFile::fake()->image('reference.png')])->assertRedirect($index);
        $profile = $employee->faceProfiles()->sole();
        $this->assertSame($admin->id, $profile->uploaded_by_user_id);
        Storage::disk('local')->assertExists($profile->reference_photo_path);
        Storage::disk('public')->assertMissing($profile->reference_photo_path);
        $photo = route('admin.employees.face-profiles.photo', [$employee, $profile]);
        $this->get($index)->assertOk()->assertSee($photo)->assertDontSee($profile->reference_photo_path);
        $this->get($photo)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('admin.employees.face-profiles.photo', [$other, $profile]))->assertNotFound();
        $this->actingAs(User::factory()->create()->refresh())->get($photo)->assertForbidden();
    }

    public function test_jpeg_upload_discards_exif_after_applying_orientation(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $employee = Employee::factory()->create();
        $original = UploadedFile::fake()->image('reference.jpg', 40, 20);
        $jpeg = file_get_contents($original->getPathname());
        $exif = "Exif\0\0II".pack('vVv', 42, 8, 1).pack('vvVV', 0x0112, 3, 1, 6).pack('V', 0);
        $photo = UploadedFile::fake()->createWithContent(
            'reference.jpg',
            substr($jpeg, 0, 2)."\xFF\xE1".pack('n', strlen($exif) + 2).$exif.substr($jpeg, 2),
        );

        $this->post(route('admin.employees.face-profiles.store', $employee), ['photo' => $photo])
            ->assertRedirectToRoute('admin.employees.face-profiles.index', $employee);

        $profile = $employee->faceProfiles()->sole();
        $stored = Storage::disk('local')->path($profile->reference_photo_path);
        $this->assertSame([20, 40], array_slice(getimagesize($stored), 0, 2));
        $this->assertStringNotContainsString("Exif\0\0", file_get_contents($stored));
        $this->assertSame(hash_file('sha256', $stored), $profile->reference_photo_hash);
    }
}
