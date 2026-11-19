<?php

namespace Tests\Feature\Scheduling;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeEmploymentPeriod;
use App\Organization\Models\AttendanceLocation;
use App\Organization\Models\AttendanceMethod;
use App\Organization\Models\AttendanceTime;
use App\Organization\Models\OutOfLocationReason;
use App\Scheduling\Models\AttendanceRecord;
use App\Scheduling\Models\EmployeeDeviceLock;
use App\Scheduling\Models\RegisteredDevice;
use App\Scheduling\Models\WorkShift;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttendanceRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_filters_and_keeps_the_filter_values_for_pagination(): void
    {
        $this->actingAs(User::factory()->create()->refresh())->withoutVite();
        $employee = Employee::factory()->create(['full_name' => 'Nadia Contoh', 'employee_code' => 'PGW-NADIA']);
        $record = AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'late_minutes' => 7]);
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'work_date' => '2026-01-06', 'check_out_at' => now()]);
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'work_date' => '2026-01-07', 'check_in_at' => null]);
        AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'work_date' => '2026-01-08']);
        AttendanceRecord::factory()->create();
        $index = route('admin.attendance-records.index');

        foreach (['nadia contoh', 'pgw-nadia'] as $search) {
            $response = $this->get($index.'?'.http_build_query([
                'search' => $search, 'date_from' => '2026-01-05', 'date_to' => '2026-01-07', 'missing_checkout' => 1,
            ]))->assertOk();
            $page = $response->viewData('records');
            $this->assertSame([$record->id], $page->modelKeys());
            $this->assertEquals($record->check_in_at, $page->first()->check_in_at);
            $this->assertNull($page->first()->check_out_at);
            $this->assertSame(7, $page->first()->late_minutes);
            $this->assertStringContainsString('missing_checkout=1', $page->url(2));
        }
        $this->getJson($index.'?date_from=2026-02-01&date_to=2026-01-01')
            ->assertUnprocessable()->assertJsonValidationErrors('date_to');
    }

    public function test_admin_records_both_events_with_an_overnight_snapshot_and_private_evidence(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-19 12:00', 'Asia/Jakarta'));
        Storage::fake('local');
        $admin = User::factory()->for(Role::where('code', 'attendance_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $employee = Employee::factory()->create();
        EmployeeEmploymentPeriod::factory()->create(['employee_id' => $employee->id]);
        $time = AttendanceTime::factory()->create([
            'check_in_start' => '20:00',
            'expected_check_in' => '21:00',
            'expected_check_out' => '06:00',
            'expected_check_out_day' => 1,
            'check_out_end' => '07:00',
            'check_out_end_day' => 1,
            'late_tolerance_minutes' => 5,
        ]);
        $shift = WorkShift::factory()->for($time, 'attendanceTime')->create();
        $override = $employee->shiftOverrides()->make(['work_shift_id' => $shift->id, 'work_date' => '2026-11-18', 'status' => 'scheduled']);
        $override->created_by_user_id = $admin->id;
        $override->saveOrFail();
        $method = AttendanceMethod::factory()->create(['requires_location' => true, 'requires_registered_device' => true]);
        $location = AttendanceLocation::factory()->create(['latitude' => -6.2, 'longitude' => 106.8, 'radius_meter' => 100]);
        $reason = OutOfLocationReason::factory()->create(['requires_note' => true, 'is_active' => true]);
        $device = RegisteredDevice::factory()->create();
        $lock = EmployeeDeviceLock::factory()->create([
            'employee_id' => $employee->id,
            'registered_device_id' => $device->id,
            'locked_at' => '2026-11-18 00:00:00',
        ]);
        $data = [
            'employee_code' => $employee->employee_code,
            'work_date' => '2026-11-18',
            'recorded_at' => '2026-11-18T21:12',
            'method_id' => $method->id,
            'location_id' => $location->id,
            'device_id' => $device->id,
            'latitude' => -6.2,
            'longitude' => 106.8,
        ];

        $this->get(route('admin.attendance-records.create'))->assertOk()->assertSee('Waktu masuk (WIB)');
        $this->post(route('admin.attendance-records.store'), [...$data, 'recorded_at' => '2026-11-18T19:59'])
            ->assertSessionHasErrors('recorded_at');
        $this->post(route('admin.attendance-records.store'), [
            ...$data, 'selfie' => UploadedFile::fake()->image('masuk.jpg'),
        ])->assertSessionHasNoErrors();
        $record = AttendanceRecord::query()->sole();
        $this->assertSame($override->id, $record->employee_shift_schedule_id);
        $this->assertSame($method->id, $record->attendance_method_id);
        $this->assertSame($location->id, $record->attendance_location_id);
        $this->assertSame($device->id, $record->registered_device_id);
        $this->assertSame(7, $record->late_minutes);
        $this->assertSame(0.0, (float) $record->distance_meter);
        Storage::disk('local')->assertExists($record->check_in_selfie_path);
        $this->post(route('admin.attendance-records.store'), $data)->assertSessionHasErrors('work_date');

        $time->update(['expected_check_out' => '06:30']);
        $location->update(['latitude' => -6.21]);
        $checkout = route('admin.attendance-records.checkout', $record);
        $this->patch($checkout, ['recorded_at' => '2026-11-18T20:00'])->assertSessionHasErrors('recorded_at');
        $this->patch($checkout, ['recorded_at' => '2026-11-19T07:01'])->assertSessionHasErrors('recorded_at');
        $outside = ['recorded_at' => '2026-11-19T06:10', 'latitude' => -6.21, 'longitude' => 106.8];
        $lock->forceFill(['released_at' => '2026-11-18 23:00:00'])->saveOrFail();
        $this->patch($checkout, $outside)->assertSessionHasErrors('recorded_at');
        $lock->forceFill(['released_at' => '2026-11-19 00:00:00'])->saveOrFail();
        $this->patch($checkout, $outside)->assertSessionHasErrors('reason_id');
        $this->patch($checkout, [...$outside, 'reason_id' => $reason->id])->assertSessionHasErrors('location_note');
        $this->patch($checkout, [
            ...$outside,
            'reason_id' => $reason->id,
            'location_note' => 'Penugasan lapangan.',
            'selfie' => UploadedFile::fake()->image('pulang.jpg'),
        ])->assertSessionHasNoErrors();

        $record->refresh();
        $this->assertSame('2026-11-19 06:00', $record->scheduled_check_out_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i'));
        $this->assertSame('2026-11-19 06:10', $record->check_out_at->setTimezone('Asia/Jakarta')->format('Y-m-d H:i'));
        $this->assertSame($reason->id, $record->check_out_reason_id);
        $this->assertGreaterThan(100, (float) $record->check_out_distance_meter);
        $this->assertSame('offsite', $record->status);
        Storage::disk('local')->assertExists($record->check_out_selfie_path);
        $this->get(route('admin.attendance-records.show', $record))->assertOk()->assertSee('Penugasan lapangan.');
        $this->patch($checkout, $outside)->assertSessionHasErrors('recorded_at');
    }

    public function test_selfie_requires_an_active_admin(): void
    {
        $record = AttendanceRecord::factory()->create();
        $url = route('admin.attendance-records.photo', [$record, 'masuk']);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['status' => 'inactive'])->refresh());
        $this->get($url)->assertForbidden();
    }
}
