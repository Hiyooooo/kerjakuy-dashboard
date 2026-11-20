<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_manage_each_employee_history_without_changing_the_coaching_author(): void
    {
        $this->withoutVite();
        $employee = Employee::factory()->create();
        $actor = $this->userWithRole('hrd_admin');
        $editor = $this->userWithRole('hrd_admin');

        $histories = [
            'educations' => ['institution_name' => 'Universitas Contoh', 'education_level' => 'S1', 'start_year' => 2019, 'graduation_year' => 2023],
            'trainings' => ['title' => 'Pelatihan Keselamatan', 'start_date' => '2025-02-08', 'end_date' => '2025-02-09'],
            'coachings' => ['subject' => 'Orientasi Pegawai', 'coaching_date' => '2025-02-10', 'follow_up' => 'Evaluasi bulan depan'],
        ];

        foreach ($histories as $segment => $attributes) {
            $this->actingAs($actor);
            $prefix = "admin.employees.$segment";
            $index = route("$prefix.index", $employee);
            $create = route("$prefix.create", $employee);
            $this->get($index)->assertOk()->assertSee('Belum ada riwayat');
            $this->get($create)->assertOk()->assertSee('Simpan');
            $this->post(route("$prefix.store", $employee), $attributes)->assertRedirect($index)->assertSessionHasNoErrors();

            $record = $employee->{$segment}()->sole();
            $primaryField = array_key_first($attributes);
            $this->get($index)->assertOk()->assertSee($attributes[$primaryField]);
            $edit = route("$prefix.edit", [$employee, $record]);
            $version = $this->get($edit)->assertOk()->viewData('version');
            $update = route("$prefix.update", [$employee, $record]);
            $this->actingAs($editor)->put($update, [...$attributes, $primaryField => 'Data diperbarui', 'edit_version' => $version])
                ->assertRedirect($index)->assertSessionHasNoErrors();
            $this->put($update, [...$attributes, 'edit_version' => $version])
                ->assertRedirect($edit)->assertSessionHasErrorsIn($edit, 'edit_version');
            $this->assertSame('Data diperbarui', $record->refresh()->{$primaryField});
        }

        $this->assertSame($actor->id, $employee->coachings()->sole()->created_by_user_id);
        $response = $this->actingAs($this->userWithRole('super_admin'))->delete(route('admin.accounts.destroy', $actor));
        $response->assertRedirectToRoute('admin.accounts.edit', $actor)
            ->assertSessionHasErrorsIn('accountDeletion', 'account_deletion');
        $this->withCookie(config('session.cookie'), session()->getId())
            ->get(route('admin.accounts.edit', $actor))->assertOk()->assertSee('Nonaktifkan akun untuk mempertahankan riwayat pembinaan.');
    }

    public function test_dates_and_years_must_be_in_order(): void
    {
        $this->actingAs($this->userWithRole('hrd_admin'));
        $employee = Employee::factory()->create();
        $this->postJson(route('admin.employees.educations.store', $employee), [
            'institution_name' => 'Universitas Contoh', 'education_level' => 'S1', 'start_year' => 2022, 'graduation_year' => 2020,
        ])->assertUnprocessable()->assertJsonValidationErrors('graduation_year');
        $this->postJson(route('admin.employees.trainings.store', $employee), [
            'title' => 'Pelatihan', 'start_date' => '2025-02-09', 'end_date' => '2025-02-08',
        ])->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->postJson(route('admin.employees.coachings.store', $employee), [
            'subject' => 'Pembinaan',
        ])->assertUnprocessable()->assertJsonValidationErrors('coaching_date');
    }

    public function test_training_certificate_is_private_scoped_and_replaced_only_when_uploaded(): void
    {
        Storage::fake('local');
        $this->actingAs($this->userWithRole('hrd_admin'))->withoutVite();
        $employee = Employee::factory()->create();
        $other = Employee::factory()->create();
        $title = ['title' => 'Pelatihan Keselamatan'];
        $index = route('admin.employees.trainings.index', $employee);
        $this->post(route('admin.employees.trainings.store', $employee), [
            ...$title, 'certificate' => UploadedFile::fake()->create('sertifikat.pdf', 100, 'application/pdf'),
        ])->assertRedirect($index)->assertSessionHasNoErrors();
        $training = $employee->trainings()->sole();
        $initialPath = $training->certificate_file_path;
        $download = route('admin.employees.trainings.certificate', [$employee, $training]);
        $this->get($download)->assertDownload('sertifikat-pelatihan-'.$training->id.'.pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get(route('admin.employees.trainings.certificate', [$other, $training]))->assertNotFound();
        $this->get(route('admin.employees.trainings.edit', [$other, $training]))->assertNotFound();

        $edit = route('admin.employees.trainings.edit', [$employee, $training]);
        $update = route('admin.employees.trainings.update', [$employee, $training]);
        $version = $this->get($edit)->assertOk()->viewData('version');
        $this->put(route('admin.employees.trainings.update', [$other, $training]), [...$title, 'edit_version' => $version])->assertNotFound();
        $this->put($update, [...$title, 'edit_version' => $version])->assertRedirect($index)->assertSessionHasNoErrors();
        $this->assertSame($initialPath, $training->refresh()->certificate_file_path);
        Storage::disk('local')->assertExists($initialPath);

        $this->put($update, [
            ...$title, 'edit_version' => $this->get($edit)->viewData('version'),
            'certificate' => UploadedFile::fake()->image('sertifikat-baru.png'),
        ])->assertRedirect($index)->assertSessionHasNoErrors();
        Storage::disk('local')->assertMissing($initialPath);
        Storage::disk('local')->assertExists($training->refresh()->certificate_file_path);
        $this->get($download)->assertDownload('sertifikat-pelatihan-'.$training->id.'.png');

        $version = $this->get($edit)->viewData('version');
        $replacementPath = $training->certificate_file_path;
        $this->put($update, [
            ...$title, 'edit_version' => $version, 'remove_certificate' => '1',
            'certificate' => UploadedFile::fake()->image('pengganti.png'),
        ])->assertSessionHasErrorsIn($edit, 'certificate');
        Storage::disk('local')->assertExists($replacementPath);
        $this->actingAs($this->userWithRole('attendance_admin'))->get($download)->assertForbidden();
        $this->get(route('admin.employees.coachings.index', $employee))->assertForbidden();
        $this->put($update, [...$title, 'edit_version' => $version, 'remove_certificate' => '1'])->assertForbidden();

        $this->actingAs($this->userWithRole('hrd_admin'));
        $this->put($update, [...$title, 'edit_version' => $version, 'remove_certificate' => '1'])
            ->assertRedirect($index)->assertSessionHasNoErrors();
        $this->assertNull($training->refresh()->certificate_file_path);
        Storage::disk('local')->assertMissing($replacementPath);
        $this->get($download)->assertNotFound();
    }

    private function userWithRole(string $code): User
    {
        return User::factory()->for(Role::where('code', $code)->firstOrFail())->create()->refresh();
    }
}
