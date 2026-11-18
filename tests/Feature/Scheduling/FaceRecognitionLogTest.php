<?php

namespace Tests\Feature\Scheduling;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Organization\Models\FaceTolerancePolicy;
use App\Scheduling\Models\AttendanceRecord;
use App\Scheduling\Services\FaceRecognitionLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use LogicException;
use Tests\TestCase;

class FaceRecognitionLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        $this->artisan('migrate')->assertSuccessful();
    }

    public function test_failed_attempt_is_visible_without_an_attendance_record(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create()->refresh();
        $employee = Employee::factory()->create();
        $capturedAt = CarbonImmutable::parse('2027-01-05 00:10', 'Asia/Jakarta');
        $profile = $employee->faceProfiles()->create([
            'reference_photo_path' => 'reference.jpg',
            'reference_photo_hash' => hash('sha256', 'fixture'),
            'uploaded_by_user_id' => $admin->id,
            'uploaded_at' => $capturedAt->subDay()->utc(),
        ]);
        FaceTolerancePolicy::factory()->create([
            'effective_start_date' => '2027-01-05',
            'effective_end_date' => null,
            'minimum_match_score' => 85,
        ]);

        $service = app(FaceRecognitionLogService::class);
        $failed = $service->record($employee, 'check_in', $capturedAt, 84.99, $profile, UploadedFile::fake()->image('verification.jpg'));
        $this->assertSame('failed', $failed->status);
        $this->assertNull($failed->attendance_record_id);
        $this->assertSame('85.00', $failed->minimum_match_score);
        $this->assertSame('2027-01-04 17:10:00', $failed->captured_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('attendance_records', 0);
        Storage::disk('local')->assertExists($failed->captured_photo_path);

        $attendance = AttendanceRecord::factory()->create(['employee_id' => $employee->id, 'work_date' => '2027-01-05']);
        $passed = $service->record($employee, 'check_out', $capturedAt, 85, $profile, attendance: $attendance);
        $this->assertSame('passed', $passed->status);
        $this->assertSame($attendance->id, $passed->attendance_record_id);

        $photo = route('admin.face-verification-logs.photo', $failed);
        $this->get($photo)->assertRedirect(route('login'));
        $this->actingAs($admin)->withoutVite();
        $this->get(route('admin.face-verification-logs.index', ['status' => 'failed']))
            ->assertOk()->assertSee('Skor di bawah batas minimum.')->assertSee('Belum terhubung ke catatan absensi.')
            ->assertDontSee('Lihat absensi')->assertDontSee('Lihat foto verifikasi');
        $this->get($photo)->assertForbidden();
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh());
        $this->get($photo)->assertOk()->assertHeader('Cache-Control', 'no-store, private');

        $profile->forceFill(['deactivated_at' => $capturedAt->utc()])->saveOrFail();
        $expired = $service->record($employee, 'check_in', $capturedAt, 100, $profile);
        $this->assertSame('failed', $expired->status);
        $this->assertSame('Profil referensi wajah tidak berlaku pada waktu pengambilan.', $expired->failure_reason);

        $this->expectException(LogicException::class);
        DB::transaction(fn () => $service->record($employee, 'check_in', $capturedAt));
    }

    public function test_failed_attempt_survives_when_photo_cannot_be_saved(): void
    {
        $photo = UploadedFile::fake()->image('verification.jpg');
        Storage::shouldReceive('disk->putFileAs')->once()->andReturn(false);
        $log = app(FaceRecognitionLogService::class)->record(
            Employee::factory()->create(), 'check_in', CarbonImmutable::now(), photo: $photo,
        );

        $this->assertDatabaseHas('face_recognition_logs', [
            'id' => $log->id, 'status' => 'failed', 'captured_photo_path' => null, 'attendance_record_id' => null,
        ]);
    }
}
