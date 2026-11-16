<?php

namespace Tests\Feature\Organization;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\Holiday;
use App\Organization\Models\LeaveCategory;
use App\Organization\Models\Office;
use App\Organization\Services\HolidayService;
use App\Organization\Services\LeaveCategoryService;
use Database\Seeders\LeaveCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LeaveCategoryAndHolidayTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_manages_leave_categories_and_handles_duplicate_names(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $this->get(route('admin.leave-categories.index'))->assertOk()->assertSee('Belum ada kategori cuti');
        $this->get(route('admin.leave-categories.create'))->assertOk()->assertSee('Nama kategori cuti');

        $data = ['name' => 'Cuti tahunan', 'description' => 'Cuti reguler', 'is_active' => '1'];
        $this->post(route('admin.leave-categories.store'), $data)->assertRedirectToRoute('admin.leave-categories.index');
        $category = LeaveCategory::sole();
        $duplicate = [...$data, 'name' => 'CUTI TAHUNAN'];
        $this->post(route('admin.leave-categories.store'), $duplicate)->assertSessionHasErrors('name');
        $this->assertThrows(
            fn () => app(LeaveCategoryService::class)->create($duplicate),
            fn (ValidationException $exception): bool => isset($exception->errors()['name']),
        );
        $this->get(route('admin.leave-categories.edit', $category))->assertOk()->assertSee('Cuti reguler');
        $this->put(route('admin.leave-categories.update', $category), [...$duplicate, 'is_active' => '0'])
            ->assertRedirectToRoute('admin.leave-categories.index');
        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame('CUTI TAHUNAN', $category->fresh()->name);
        $this->seed(LeaveCategorySeeder::class);
        $this->assertDatabaseCount('leave_categories', 1);
        $this->get(route('admin.leave-categories.index'))->assertOk()->assertSee('Nonaktif');
        $this->delete(route('admin.leave-categories.destroy', $category))->assertRedirectToRoute('admin.leave-categories.index');
        $this->assertModelMissing($category);
    }

    public function test_super_admin_manages_global_and_office_holidays(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'super_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $this->get(route('admin.holidays.index'))->assertOk()->assertSee('Belum ada hari libur');
        $this->get(route('admin.holidays.create'))->assertOk()->assertSee('Semua kantor');

        $office = Office::create(['code' => 'BDG', 'name' => 'Bandung']);
        $data = ['name' => 'Libur perusahaan', 'holiday_date' => '2028-02-29', 'office_id' => 'all'];
        $this->post(route('admin.holidays.store'), $data)->assertRedirectToRoute('admin.holidays.index');
        $global = Holiday::sole();
        $this->assertNull($global->office_id);

        $this->post(route('admin.holidays.store'), [...$data, 'name' => 'Libur cabang', 'office_id' => $office->id])
            ->assertRedirectToRoute('admin.holidays.index');
        $local = Holiday::whereBelongsTo($office)->sole();
        $this->get(route('admin.holidays.index', ['filter_year' => 2028]))->assertOk()
            ->assertSee('Semua kantor')->assertSee('Bandung (BDG)')->assertSee('29 Februari 2028');
        $this->get(route('admin.holidays.edit', $local))->assertOk()
            ->assertSee('value="2028-02-29"', false)->assertSee('value="'.$office->id.'" selected', false);
        $this->from(route('admin.offices.edit', $office))->delete(route('admin.offices.destroy', $office))
            ->assertSessionHasErrors('delete');

        $this->put(route('admin.holidays.update', $local), [...$data, 'name' => 'Libur khusus'])
            ->assertRedirectToRoute('admin.holidays.index');
        $this->assertNull($local->fresh()->office_id);
        $this->assertSame('Libur khusus', $local->fresh()->name);
        $this->delete(route('admin.holidays.destroy', $local))->assertRedirectToRoute('admin.holidays.index');
        $this->assertModelMissing($local);
        $this->assertModelExists($global);
    }

    public function test_holiday_filters_include_global_holidays_and_keep_context_after_edit(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $office = Office::create(['name' => 'Bandung', 'code' => 'BDG']);
        $inactive = Office::create(['name' => 'Surabaya', 'code' => 'SBY', 'is_active' => false]);
        $local = Holiday::factory()->create(['holiday_date' => '2028-02-29', 'name' => 'Libur Bandung', 'office_id' => $office->id]);
        $history = Holiday::factory()->create(['holiday_date' => '2028-02-29', 'name' => 'Libur Surabaya', 'office_id' => $inactive->id]);
        Holiday::factory()->create(['holiday_date' => '2028-02-29', 'name' => 'Libur global']);
        Holiday::factory()->create(['holiday_date' => '2027-02-28', 'name' => 'Tahun lalu']);
        $filters = ['filter_year' => 2028, 'office' => $office->id];
        $index = route('admin.holidays.index', $filters);

        $this->get($index)->assertOk()->assertSee('Libur Bandung')->assertSee('Libur global')
            ->assertDontSee('Libur Surabaya')->assertDontSee('Tahun lalu');
        $this->get(route('admin.holidays.create', $filters))->assertOk()->assertDontSee('SBY');
        $this->get(route('admin.holidays.edit', $history))->assertOk()->assertSee('SBY')->assertSee('29 Februari 2028');
        $data = ['name' => 'Libur baru', 'holiday_date' => '2028-02-29', 'office_id' => $inactive->id];
        $this->post(route('admin.holidays.store', $filters), $data)->assertSessionHasErrors('office_id');
        $this->put(route('admin.holidays.update', [$local, ...$filters]), $data)->assertSessionHasErrors('office_id');
        $this->put(route('admin.holidays.update', [$history, ...$filters]), $data)->assertRedirect($index);
        $this->delete(route('admin.holidays.destroy', [$local, ...$filters]))->assertRedirect($index);
    }

    public function test_invalid_office_requires_an_explicit_new_choice(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $office = Office::create(['code' => 'QA', 'name' => 'Kantor QA']);
        $data = ['name' => 'Libur kantor', 'holiday_date' => '2028-02-29'];
        $form = route('admin.holidays.create');
        $store = route('admin.holidays.store');

        $this->get($form)->assertOk()->assertSee('value="" disabled selected>Pilih cakupan', false);
        $office->delete();
        $this->from($form)->post($store, [...$data, 'office_id' => $office->id])
            ->assertRedirect($form)->assertSessionHasErrors('office_id');
        $this->get($form)->assertOk()
            ->assertSee('value="" disabled selected>Pilih cakupan', false)
            ->assertDontSee('value="all" selected', false);

        $this->post($store, $data)->assertSessionHasErrors('office_id');
        $this->post($store, [...$data, 'office_id' => ''])->assertSessionHasErrors('office_id');
        $this->assertDatabaseCount('holidays', 0);
        $this->post($store, [...$data, 'office_id' => 'all'])->assertRedirectToRoute('admin.holidays.index');
        $this->assertNull(Holiday::sole()->office_id);
    }

    public function test_duplicate_holidays_are_rejected_within_each_scope(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $office = Office::create(['code' => 'BDG', 'name' => 'Bandung']);
        $store = route('admin.holidays.store');
        $data = ['name' => 'Libur', 'holiday_date' => '2028-02-29'];

        foreach (['all' => null, $office->id => $office->id] as $input => $officeId) {
            $payload = [...$data, 'office_id' => $input];
            $this->post($store, $payload)->assertRedirectToRoute('admin.holidays.index');
            $this->post($store, [...$payload, 'description' => 'Catatan berbeda'])->assertSessionHasErrors('name');
            $this->assertThrows(
                fn () => app(HolidayService::class)->create([...$data, 'office_id' => $officeId]),
                fn (ValidationException $exception): bool => isset($exception->errors()['name']),
            );
        }

        $global = Holiday::whereNull('office_id')->sole();
        $local = Holiday::whereBelongsTo($office)->sole();
        $this->put(route('admin.holidays.update', $local), [...$data, 'office_id' => 'all'])->assertSessionHasErrors('name');
        $this->put(route('admin.holidays.update', $global), [...$data, 'office_id' => 'all'])->assertRedirectToRoute('admin.holidays.index');
        $this->post($store, [...$data, 'office_id' => 'all', 'name' => 'Libur lain'])->assertRedirectToRoute('admin.holidays.index');
        $this->assertDatabaseCount('holidays', 3);
    }

    public function test_holiday_rejects_unknown_offices_and_invalid_dates(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());

        $this->post(route('admin.holidays.store'), [
            'name' => 'Libur',
            'office_id' => 99999,
            'holiday_date' => '2027-02-29',
        ])->assertSessionHasErrors(['office_id', 'holiday_date']);
        $this->post(route('admin.holidays.store'), [
            'name' => 'Libur',
            'office_id' => 'all',
            'holiday_date' => '0000-01-01',
        ])->assertSessionHasErrors('holiday_date');
        $this->assertDatabaseCount('holidays', 0);
    }

    public function test_guests_and_attendance_admins_cannot_manage_these_references(): void
    {
        foreach (['leave-categories', 'holidays'] as $resource) {
            $this->get(route("admin.{$resource}.index"))->assertRedirectToRoute('login');
        }

        $this->actingAs(User::factory()->create()->refresh())->withoutVite();
        foreach (['leave-categories' => LeaveCategory::factory()->create(), 'holidays' => Holiday::factory()->create()] as $resource => $record) {
            $this->get(route("admin.{$resource}.index"))->assertForbidden();
            $this->post(route("admin.{$resource}.store"))->assertForbidden();
            $this->delete(route("admin.{$resource}.destroy", $record))->assertForbidden();
        }

        $this->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee(route('admin.leave-categories.index'))->assertDontSee(route('admin.holidays.index'));
    }
}
