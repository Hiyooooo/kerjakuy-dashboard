<?php

namespace Tests\Feature\Organization;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Organization\Models\FaceTolerancePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaceTolerancePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_close_an_open_period_and_schedule_its_successor(): void
    {
        $this->travelTo(now('Asia/Jakarta')->setDate(2027, 1, 31)->startOfDay());
        $admin = User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $index = route('admin.face-tolerance-policies.index');
        $store = route('admin.face-tolerance-policies.store');

        $this->get($index)->assertOk()->assertSee('Belum ada kebijakan');
        $this->get(route('admin.face-tolerance-policies.create'))->assertOk()->assertSee('Berlaku sampai (opsional)');

        $data = ['minimum_match_score' => '85', 'effective_start_date' => '2027-01-01'];
        $this->post($store, $data)->assertRedirect($index);
        $policy = FaceTolerancePolicy::sole();
        $this->assertSame($admin->id, $policy->created_by_user_id);
        $this->assertNull($policy->effective_end_date);
        $this->get(route('admin.face-tolerance-policies.edit', $policy))->assertOk()->assertSee('2027-01-01');

        $successor = ['minimum_match_score' => '90.25', 'effective_start_date' => '2027-02-01'];
        $this->post($store, $successor)->assertSessionHasErrors('effective_start_date');
        $this->put(route('admin.face-tolerance-policies.update', $policy), [
            ...$data,
            'effective_end_date' => '2027-01-31',
            'created_by_user_id' => $admin->id + 1,
            'edit_version' => $policy->editVersion(),
        ])->assertRedirect($index);
        $this->assertSame($admin->id, $policy->refresh()->created_by_user_id);

        $this->post($store, [...$successor, 'effective_start_date' => '2027-01-31'])
            ->assertSessionHasErrors('effective_start_date');
        $this->post($store, $successor)->assertRedirect($index);
        $this->get($index)->assertOk()->assertSee('Berlaku')->assertSee('Terjadwal')->assertSee('90,25 / 100');

        $this->travel(1)->day();
        $this->get($index)->assertOk()->assertSee('Berakhir');
    }

    public function test_stale_form_cannot_overwrite_the_latest_policy(): void
    {
        $admin = User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $policy = FaceTolerancePolicy::factory()->create()->refresh();
        $data = [
            'minimum_match_score' => '85',
            'effective_start_date' => $policy->effective_start_date->toDateString(),
            'edit_version' => $policy->editVersion(),
        ];
        $update = route('admin.face-tolerance-policies.update', $policy);
        $this->put($update, [...$data, 'minimum_match_score' => '90'])
            ->assertRedirectToRoute('admin.face-tolerance-policies.index');

        $this->followingRedirects()->from(route('admin.face-tolerance-policies.edit', $policy))
            ->put($update, $data)->assertOk()->assertSee('Buka versi terbaru');

        $this->assertSame('90.00', $policy->refresh()->minimum_match_score);
    }

    public function test_scheduling_a_replacement_rolls_back_the_closure_if_the_new_period_overlaps(): void
    {
        $this->travelTo(today('Asia/Jakarta')->setDate(2027, 1, 1));
        $admin = User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $policy = FaceTolerancePolicy::factory()->create([
            'effective_start_date' => '2027-01-01',
            'effective_end_date' => '2027-06-30',
        ])->refresh();
        FaceTolerancePolicy::factory()->create([
            'effective_start_date' => '2027-07-01',
            'effective_end_date' => null,
        ]);
        $this->get(route('admin.face-tolerance-policies.replacement', $policy))->assertOk()->assertSee('Jadwalkan pengganti');
        $replace = route('admin.face-tolerance-policies.replace', $policy);
        $data = [
            'minimum_match_score' => '92',
            'effective_start_date' => '2027-02-01',
            'edit_version' => $policy->editVersion(),
        ];

        $this->post($replace, $data)->assertSessionHasErrors('effective_start_date');
        $this->assertSame('2027-06-30', $policy->refresh()->effective_end_date->toDateString());
        $this->assertDatabaseCount('face_tolerance_policies', 2);

        $this->post($replace, [...$data, 'effective_end_date' => '2027-06-30'])
            ->assertRedirectToRoute('admin.face-tolerance-policies.index');
        $this->assertSame('2027-01-31', $policy->refresh()->effective_end_date->toDateString());
        $replacement = FaceTolerancePolicy::whereDate('effective_start_date', '2027-02-01')->sole();
        $this->assertSame('92.00', $replacement->minimum_match_score);
        $this->assertSame($admin->id, $replacement->created_by_user_id);
    }

    public function test_invalid_scores_and_dates_are_rejected(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'super_admin')->firstOrFail())->create()->refresh())
            ->withoutVite();

        $this->followingRedirects()->from(route('admin.face-tolerance-policies.create'))
            ->post(route('admin.face-tolerance-policies.store'), [
                'minimum_match_score' => '100.01',
                'effective_start_date' => '2027-01-02',
                'effective_end_date' => '2027-01-01',
            ])->assertOk()->assertSee('id="minimum_match_score-error"', false)
            ->assertSee('id="effective_end_date-error"', false);
        $this->assertDatabaseCount('face_tolerance_policies', 0);
    }

    public function test_access_is_limited_to_organization_admins(): void
    {
        $this->get(route('admin.face-tolerance-policies.index'))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create()->refresh());
        $this->get(route('admin.face-tolerance-policies.index'))->assertForbidden();
        $this->post(route('admin.face-tolerance-policies.store'))->assertForbidden();
    }
}
