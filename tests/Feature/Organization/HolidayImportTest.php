<?php

namespace Tests\Feature\Organization;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\Holiday;
use App\Organization\Models\Office;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HolidayImportTest extends TestCase
{
    use RefreshDatabase;

    private const string ENDPOINT = 'https://indonesian-holidays.ahrulsyamil.com/holidays/2026';

    public function test_import_prioritizes_official_data_and_keeps_manual_holidays(): void
    {
        Http::preventStrayRequests();
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();

        $national = ['date' => '2026-01-01', 'name' => 'Tahun Baru Masehi', 'type' => 'national_holiday', 'description' => 'New Year'];
        $joint = ['date' => '2026-01-01', 'name' => 'Cuti bersama', 'type' => 'joint_leave'];
        Http::fake([self::ENDPOINT => Http::response([
            'meta' => ['year' => 2026, 'count' => 3],
            'data' => [[...$national, 'type' => 'joint_leave'], $national, $joint],
        ])]);

        $data = ['holiday_date' => $national['date'], 'name' => $national['name'], 'description' => 'Catatan HR'];
        $existing = Holiday::create([...$data, 'office_id' => null]);
        $office = Office::create(['name' => 'Jakarta', 'code' => 'JKT']);
        $manual = Holiday::create([...$data, 'office_id' => $office->id]);

        $import = route('admin.holidays.import', ['filter_year' => 2025, 'office' => $office->id]);
        $index = route('admin.holidays.index', ['filter_year' => 2026, 'office' => $office->id]);
        $this->post($import, ['year' => 2026])->assertRedirect($index)
            ->assertSessionHas('holiday_import_result.updated', ['2026-01-01 Tahun Baru Masehi']);
        $this->post($import, ['year' => 2026])->assertRedirect($index)
            ->assertSessionHas('holiday_import_result.unchanged', 2);

        $this->assertDatabaseCount('holidays', 3);
        $this->assertSame('national_holiday', $existing->fresh()->official_type);
        $this->assertSame('Catatan HR', $existing->fresh()->description);
        $this->assertNull($manual->fresh()->official_type);
        $jointHoliday = Holiday::where('official_type', 'joint_leave')->sole();
        $this->assertNull($jointHoliday->office_id);
        $this->get($index)->assertOk()
            ->assertSeeInOrder(['Libur nasional', 'Cuti bersama', 'Jakarta (JKT)'])
            ->assertSee('Impor libur resmi')->assertSee('Tambah libur manual')->assertSee('Impor terakhir');
        $this->get(route('admin.holidays.edit', $manual))->assertOk();
        $this->get(route('admin.holidays.edit', $existing))->assertForbidden();
        $this->put(route('admin.holidays.update', $existing), [...$data, 'office_id' => 'all'])->assertForbidden();
        $this->delete(route('admin.holidays.destroy', $existing))->assertForbidden();
        $this->assertModelExists($existing);
    }

    public function test_import_reconciles_revisions_and_restores_previous_manual_entries(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $manual = Holiday::factory()->create(['name' => 'Libur internal', 'holiday_date' => '2026-12-22']);
        $otherYear = Holiday::factory()->create(['holiday_date' => '2025-12-25']);
        $otherYear->forceFill(['official_type' => 'national_holiday'])->save();
        $old = ['date' => '2026-12-20', 'name' => 'Libur resmi', 'type' => 'national_holiday'];
        $matched = [...$old, 'name' => $manual->name, 'date' => '2026-12-22'];
        $revised = [...$old, 'date' => '2026-12-21'];
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['meta' => ['year' => 2026, 'count' => 2], 'data' => [$old, $matched]])
            ->push(['meta' => ['year' => 2026, 'count' => 1], 'data' => [$revised]])]);

        $import = route('admin.holidays.import');
        $this->post($import, ['year' => 2026])->assertRedirect();
        $obsolete = Holiday::where('name', 'Libur resmi')->sole();
        $this->post($import, ['year' => 2026])
            ->assertSessionHas('holiday_import_result.added', ['2026-12-21 Libur resmi'])
            ->assertSessionHas('holiday_import_result.withdrawn', ['2026-12-20 Libur resmi', '2026-12-22 Libur internal']);

        $this->assertModelMissing($obsolete);
        $this->assertModelExists($otherYear);
        $this->assertNull($manual->fresh()->official_type);
        $this->assertSame('2026-12-21', Holiday::where('name', 'Libur resmi')->sole()->holiday_date->toDateString());
    }

    public function test_invalid_or_unavailable_api_data_does_not_change_saved_holidays(): void
    {
        Http::preventStrayRequests();
        Http::fake([self::ENDPOINT => Http::sequence()
            ->push(['data' => [
                ['date' => '2026-01-01', 'name' => 'Libur baru', 'type' => 'national_holiday'],
                ['date' => '2027-01-01', 'name' => 'Di luar tahun', 'type' => 'national_holiday'],
            ]])
            ->push(['meta' => ['year' => 2026, 'count' => 3], 'data' => []])
            ->push(['meta' => ['year' => 2026, 'count' => 0], 'data' => []])
            ->push([], 404)
            ->push([], 429)]);

        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $manual = Holiday::factory()->create();
        $index = route('admin.holidays.index');
        $import = route('admin.holidays.import');

        foreach ([
            'Respons API hari libur tidak valid. Impor dibatalkan.',
            'Daftar libur resmi tidak lengkap. Impor dibatalkan.',
            'Data libur resmi tahun 2026 belum tersedia.',
            'Data libur resmi tahun 2026 belum tersedia.',
            'Layanan libur resmi sedang bermasalah. Coba lagi nanti.',
        ] as $message) {
            $this->from($index)->post($import, ['year' => 2026])->assertRedirect($index)
                ->assertSessionHasErrors(['year' => $message]);
        }

        $this->assertModelExists($manual);
        $this->assertDatabaseCount('holidays', 1);
    }

    public function test_import_requires_organization_access_and_a_valid_year(): void
    {
        Http::fake();
        $url = route('admin.holidays.import');
        $this->post($url, ['year' => 2026])->assertRedirectToRoute('login');

        $this->actingAs(User::factory()->create()->refresh());
        $this->post($url, ['year' => 2026])->assertForbidden();

        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $this->post($url, ['year' => 'abc'])->assertSessionHasErrors('year');
        Http::assertNothingSent();
    }
}
