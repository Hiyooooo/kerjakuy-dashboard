<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\EmployeeRequest;
use App\Scheduling\Models\AttendanceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LateRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_late_request_links_own_attendance_and_snapshots_its_times(): void
    {
        Storage::fake('local');
        $admin = User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $attendance = AttendanceRecord::factory()->create([
            'work_date' => '2027-01-05',
            'scheduled_check_in_at' => '2027-01-05 01:00:00',
            'check_in_at' => '2027-01-05 01:25:00',
            'late_minutes' => 0,
        ]);
        $this->get(route('admin.late-requests.create'))->assertOk()->assertSee('Alasan keterlambatan');
        $payload = [
            'employee_code' => $attendance->employee->employee_code,
            'late_date' => '2027-01-05',
            'expected_check_in' => '09:00',
            'actual_check_in' => '10:00',
            'reason' => 'Kendala perjalanan',
        ];
        $store = route('admin.late-requests.store');
        $this->post($store, $payload)->assertSessionHasErrors('late_date');
        $this->assertDatabaseCount('requests', 0);
        $attendance->forceFill(['late_minutes' => 20])->saveOrFail();
        $this->post($store, [...$payload, 'attachment' => UploadedFile::fake()->image('bukti.png')])
            ->assertSessionHasNoErrors();

        $request = EmployeeRequest::where('request_type', 'late')->sole();
        $detail = DB::table('late_request_details')->where('request_id', $request->id)->sole();
        $this->assertSame('pending', $request->status);
        $this->assertSame($attendance->id, $detail->attendance_record_id);
        $this->assertSame('08:00', substr($detail->expected_check_in, 0, 5));
        $this->assertSame('08:25', substr($detail->actual_check_in, 0, 5));
        $this->get(route('admin.late-requests.index'))->assertOk()->assertSee($request->request_number);
        $this->get(route('admin.late-requests.show', $request))->assertOk()->assertSee('08:25 WIB')->assertSee('Menunggu persetujuan');
        $attachment = route('admin.late-requests.attachment', $request);
        $this->get($attachment)->assertDownload(basename($detail->attachment_path));
        $this->post($store, $payload)->assertSessionHasErrors('late_date');
        $request->recordDecision('approved', $admin, null);
        $this->post($store, $payload)->assertSessionHasErrors('late_date');
        $this->assertDatabaseCount('requests', 1);
        $request->recordDecision('cancelled', $admin, null);
        $this->post($store, $payload)->assertSessionHasNoErrors();

        $this->post(route('admin.late-requests.store'), [...$payload, 'late_date' => '2027-01-06'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('late_request_details', ['late_date' => '2027-01-06', 'attendance_record_id' => null, 'actual_check_in' => '10:00']);
        $this->post(route('admin.late-requests.store'), [...$payload, 'reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAs(User::factory()->create()->refresh());
        $this->get($attachment)->assertForbidden();
        $this->post(route('admin.late-requests.store'), $payload)->assertForbidden();
    }
}
