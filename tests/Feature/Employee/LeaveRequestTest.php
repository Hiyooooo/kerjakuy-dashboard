<?php

namespace Tests\Feature\Employee;

use App\Access\Models\Role;
use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Organization\Models\LeaveCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_leave_filters_and_available_balance_include_only_approved_usage(): void
    {
        $admin = User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh();
        $this->actingAs($admin)->withoutVite();
        $employee = Employee::factory()->create(['full_name' => 'Rina Contoh', 'employee_code' => 'PGW-RINA']);
        $category = LeaveCategory::factory()->create();
        $common = ['employee_code' => $employee->employee_code, 'leave_category_id' => $category->id];
        $this->post(route('admin.leave-requests.entitlements.store'), [
            ...$common, 'entitlement_year' => 2027, 'granted_days' => '1.5', 'carry_over_days' => '0.25',
        ])->assertSessionHasNoErrors();
        $entitlement = DB::table('employee_leave_entitlements')->sole();
        DB::table('leave_balance_adjustments')->insert([
            'employee_leave_entitlement_id' => $entitlement->id, 'adjustment_days' => '-0.25',
            'adjusted_by_user_id' => $admin->id, 'adjusted_at' => now(), 'reason' => 'Koreksi jatah',
        ]);
        $store = route('admin.leave-requests.store');
        $this->post($store, [...$common, 'days' => [
            ['date' => '2027-02-01', 'duration' => '0.25'],
            ['date' => '2027-02-03', 'duration' => '0.5'],
        ]])->assertSessionHasNoErrors();
        $approved = EmployeeRequest::query()->sole();
        $approved->forceFill(['status' => 'approved'])->saveOrFail();
        $this->post($store, [...$common, 'days' => [['date' => '2027-02-04', 'duration' => '0.25']]])->assertSessionHasNoErrors();

        $lookup = route('admin.leave-requests.employees', ['search' => 'rina contoh']);
        $this->getJson($lookup)->assertOk()->assertJsonPath('data.0.id', $employee->id);
        $balance = route('admin.leave-requests.balance', [
            'employee_id' => $employee->id, 'leave_category_id' => $category->id,
            'dates' => ['2027-02-01', '2027-02-03', '2028-01-01'],
        ]);
        $this->getJson($balance)->assertOk()->assertExactJson(['data' => [
            ['entitlement_year' => 2027, 'available_days' => '0.75'],
            ['entitlement_year' => 2028, 'available_days' => null],
        ]]);
        foreach (['rina contoh', 'pgw-rina', strtolower($approved->request_number)] as $search) {
            $index = route('admin.leave-requests.index', ['search' => $search, 'status' => 'approved']);
            $page = $this->get($index)->assertOk()->viewData('requests');
            $this->assertSame([$approved->id], $page->modelKeys());
            $this->assertSame($category->name, $page->first()->category);
            $this->assertSame(['2027-02-01', '2027-02-03'], $page->first()->leave_dates);
            $this->assertSame('0.75', $page->first()->total_days);
            $this->assertSame('approved', $page->first()->status);
            $this->assertStringContainsString('status=approved', $page->url(2));
        }
        $this->post($store, [...$common, 'days' => [
            ['date' => '2027-02-05', 'duration' => '0.5'],
            ['date' => '2027-02-07', 'duration' => '0.26'],
        ]])->assertSessionHasErrors('days.1.duration');
        $this->assertDatabaseCount('requests', 2);
        $this->actingAs(User::factory()->create()->refresh());
        $this->getJson($balance)->assertForbidden();
    }

    public function test_admin_submits_leave_across_years_and_each_day_uses_its_own_entitlement(): void
    {
        $this->actingAs(User::factory()->for(Role::where('code', 'hrd_admin')->firstOrFail())->create()->refresh())->withoutVite();
        Storage::fake('local');
        $employee = Employee::factory()->create();
        $category = LeaveCategory::factory()->create(['is_active' => true]);
        $common = ['employee_code' => $employee->employee_code, 'leave_category_id' => $category->id];
        $grant = route('admin.leave-requests.entitlements.store');
        $submit = route('admin.leave-requests.store');
        $data = [...$common, 'days' => [
            ['date' => '2026-12-31', 'duration' => '0.5'],
            ['date' => '2027-01-04', 'duration' => '1'],
        ]];

        $this->get(route('admin.leave-requests.create'))->assertOk()->assertSee('Hari cuti');
        $this->get(route('admin.leave-requests.entitlements.create'))->assertOk()->assertSee('Tahun jatah');
        $this->post($grant, [...$common, 'entitlement_year' => 2026, 'granted_days' => 12])->assertSessionHasNoErrors();
        $this->post($submit, $data)->assertSessionHasErrors('days.1.date');
        $this->assertDatabaseCount('requests', 0);
        $this->assertDatabaseCount('leave_request_days', 0);
        $this->post($grant, [...$common, 'entitlement_year' => 2027, 'granted_days' => 12])->assertSessionHasNoErrors();
        $this->post($grant, [...$common, 'entitlement_year' => 2027, 'granted_days' => 99])->assertSessionHasErrors('entitlement_year');
        $this->post($submit, [...$common, 'days' => [$data['days'][0], $data['days'][0]]])->assertSessionHasErrors('days.1.date');

        $this->post($submit, [...$data, 'attachment' => UploadedFile::fake()->image('cuti.png')])->assertSessionHasNoErrors();
        $request = EmployeeRequest::query()->sole();
        $this->assertSame('pending', $request->status);
        $this->assertSame('leave', $request->request_type);
        $this->assertSame($employee->id, $request->employee_id);
        $this->assertDatabaseCount('leave_request_details', 1);
        $entitlements = DB::table('employee_leave_entitlements')->pluck('id', 'entitlement_year');
        foreach ($data['days'] as $day) {
            $this->assertDatabaseHas('leave_request_days', [
                'leave_request_detail_id' => $request->id,
                'employee_leave_entitlement_id' => $entitlements[(int) substr($day['date'], 0, 4)],
                'leave_date' => $day['date'],
                'duration_days' => $day['duration'],
            ]);
        }
        $path = DB::table('leave_request_details')->where('request_id', $request->id)->value('attachment_path');
        Storage::disk('local')->assertExists($path);
        $this->get(route('admin.leave-requests.index'))->assertOk()->assertSee($request->request_number);
        $this->get(route('admin.leave-requests.show', $request))->assertOk()->assertSee('Jatah 2026')->assertSee('Jatah 2027');
        $this->get(route('admin.leave-requests.attachment', $request))->assertOk();

        $this->actingAs(User::factory()->create()->refresh());
        $this->get(route('admin.leave-requests.attachment', $request))->assertForbidden();
        $this->post($submit, $data)->assertForbidden();
    }
}
