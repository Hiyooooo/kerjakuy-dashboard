<?php

namespace Tests\Feature\Scheduling;

use App\Access\Models\User;
use App\Employee\Models\EmployeeEmploymentPeriod;
use App\Scheduling\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_only_returns_attendance_linked_to_the_authenticated_account(): void
    {
        $own = AttendanceRecord::factory()->create(['check_in_selfie_path' => 'private/selfie.jpg']);
        $other = AttendanceRecord::factory()->create();
        $index = route('api.v1.attendance.index');
        $this->get($index)->assertUnauthorized()->assertJsonStructure(['message']);

        $this->actingAs(User::factory()->create()->refresh());
        $this->getJson($index)->assertForbidden();
        $account = User::factory()->create(['employee_id' => $own->employee_id, 'status' => 'inactive']);
        $this->actingAs($account->refresh());
        $this->getJson($index)->assertForbidden();

        $account->update(['status' => 'active']);
        $this->actingAs($account->refresh());
        $this->getJson($index.'?employee_id='.$other->employee_id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $own->id)
            ->assertJsonMissingPath('data.0.check_in_selfie_path')->assertHeader('Cache-Control', 'no-store, private');
        $this->getJson(route('api.v1.attendance.show', $own->id))
            ->assertOk()->assertJsonPath('data.work_date', $own->work_date->toDateString());
        $this->getJson(route('api.v1.attendance.show', $other->id))->assertNotFound();
        $this->getJson(route('api.v1.attendance.show', '99999999999999999999'))->assertNotFound();
    }

    public function test_submission_endpoints_fail_closed(): void
    {
        $record = AttendanceRecord::factory()->create();
        $this->actingAs(User::factory()->create(['employee_id' => $record->employee_id])->refresh());
        $this->app->instance('env', 'local');
        $this->withSession(['_token' => 'attendance-test-csrf']);

        $this->postJson(route('api.v1.attendance.check-in'))->assertStatus(419);
        $headers = ['X-CSRF-TOKEN' => 'attendance-test-csrf'];
        $this->postJson(route('api.v1.attendance.check-in'), [], $headers)->assertServiceUnavailable()
            ->assertJsonPath('code', 'attendance_submission_unavailable');
        $this->postJson(route('api.v1.attendance.check-out', $record->id), [], $headers)->assertServiceUnavailable()
            ->assertJsonPath('code', 'attendance_submission_unavailable');
        $this->assertDatabaseCount('attendance_records', 1);
        $this->assertNull($record->refresh()->check_out_at);
    }

    public function test_resigned_employee_can_read_their_history_while_the_account_is_active(): void
    {
        $record = AttendanceRecord::factory()->create();
        EmployeeEmploymentPeriod::factory()->create([
            'employee_id' => $record->employee_id,
            'status' => 'resigned',
            'effective_start_date' => today('Asia/Jakarta')->toDateString(),
        ]);
        $this->actingAs(User::factory()->create(['employee_id' => $record->employee_id])->refresh());

        $this->getJson(route('api.v1.attendance.index'))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $record->id);
    }
}
