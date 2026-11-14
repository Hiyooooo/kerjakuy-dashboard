<?php

namespace Tests\Feature\Organization;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\Office;
use App\Organization\Models\Position;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrganizationMasterTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_create_and_deactivate_a_position(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $this->get(route('admin.positions.index'))->assertOk()->assertSee('Belum ada jabatan');
        $this->get(route('admin.positions.create'))->assertOk()->assertSee('Tambah jabatan');
        $data = ['code' => 'HR-01', 'name' => 'Staf HR', 'description' => 'Administrasi', 'is_active' => '1'];
        $this->post(route('admin.positions.store'), $data)->assertRedirectToRoute('admin.positions.index');
        $position = Position::where('code', 'HR-01')->firstOrFail();
        $this->post(route('admin.positions.store'), $data)->assertSessionHasErrors('code');
        $this->get(route('admin.positions.edit', $position))->assertOk()->assertSee('Staf HR');
        $this->put(route('admin.positions.update', $position), [...$data, 'is_active' => '0'])
            ->assertRedirectToRoute('admin.positions.index');
        $this->assertFalse($position->fresh()->is_active);
        $this->get(route('admin.positions.index'))->assertOk()->assertSee('Nonaktif');
    }

    public function test_super_admin_can_manage_office_coordinates(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'super_admin')->firstOrFail())->create()->refresh())->withoutVite();
        $data = ['code' => 'JKT', 'name' => 'Jakarta', 'address' => 'Jakarta Pusat', 'is_active' => '1'];
        $this->post(route('admin.offices.store'), [...$data, 'latitude' => '-91'])
            ->assertSessionHasErrors(['latitude', 'longitude']);
        $this->post(route('admin.offices.store'), [...$data, 'latitude' => '-6.1753924', 'longitude' => '106.827153'])
            ->assertRedirectToRoute('admin.offices.index');
        $office = Office::where('code', 'JKT')->firstOrFail();
        $this->get(route('admin.offices.index'))->assertOk()->assertSee('Jakarta Pusat');
        $this->get(route('admin.offices.edit', $office))->assertOk()->assertSee('Koordinat kantor');
        $this->put(route('admin.offices.update', $office), [...$data, 'latitude' => null, 'longitude' => null])
            ->assertRedirectToRoute('admin.offices.index');
        $this->assertNull($office->fresh()->latitude);
        $this->assertNull($office->fresh()->longitude);
    }

    public function test_guest_and_security_cannot_manage_organization_data(): void
    {
        $this->get(route('admin.positions.index'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create()->refresh());
        $this->get(route('admin.positions.index'))->assertForbidden();
        $this->post(route('admin.offices.store'))->assertForbidden();
        $position = Position::create(['code' => 'HR', 'name' => 'Staf HR']);
        $this->delete(route('admin.positions.destroy', $position))->assertForbidden();
        $this->assertModelExists($position);
        $this->withoutVite()->get(route('admin.dashboard'))->assertOk()
            ->assertDontSee(route('admin.positions.index'))->assertDontSee(route('admin.offices.index'));
    }

    public function test_only_unreferenced_organization_records_can_be_deleted(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();

        Schema::create('organization_test_references', function (Blueprint $table): void {
            $table->foreignId('office_id')->constrained()->restrictOnDelete();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
        });

        $office = Office::create(['code' => 'HQ', 'name' => 'Kantor pusat']);
        $position = Position::create(['code' => 'HR', 'name' => 'Staf HR']);
        DB::table('organization_test_references')->insert(['office_id' => $office->id, 'position_id' => $position->id]);

        foreach (['offices' => $office, 'positions' => $position] as $resource => $record) {
            $editUrl = route("admin.{$resource}.edit", $record);
            $this->get($editUrl)->assertOk()->assertSee('secara permanen');
            $this->from($editUrl)->delete(route("admin.{$resource}.destroy", $record))
                ->assertRedirect($editUrl)->assertSessionHasErrors('delete');
            $this->assertModelExists($record);
        }

        DB::table('organization_test_references')->delete();

        foreach (['offices' => $office, 'positions' => $position] as $resource => $record) {
            $this->delete(route("admin.{$resource}.destroy", $record))->assertRedirectToRoute("admin.{$resource}.index");
            $this->assertModelMissing($record);
        }
    }
}
