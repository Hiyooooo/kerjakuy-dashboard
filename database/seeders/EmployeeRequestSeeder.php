<?php

namespace Database\Seeders;

use App\Access\Models\User;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Employee\Services\ShiftRequestService;
use App\Organization\Models\LeaveCategory;
use App\Scheduling\Models\EmployeeShiftSchedule;
use App\Scheduling\Models\WorkShift;
use App\Scheduling\Services\EmployeeScheduleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeRequestSeeder extends Seeder
{
    public function run(EmployeeScheduleService $schedules, ShiftRequestService $shifts): void
    {
        DB::transaction(function () use ($schedules, $shifts): void {
            $employee = Employee::factory()->create();
            $category = LeaveCategory::factory()->create();
            $partner = Employee::factory()->create();
            [$shift, $partnerShift] = WorkShift::factory()->count(2)->create()->all();
            $date = today('Asia/Jakarta')->addWeek();

            $entitlementId = DB::table('employee_leave_entitlements')->insertGetId([
                'employee_id' => $employee->id, 'leave_category_id' => $category->id,
                'entitlement_year' => $date->year, 'granted_days' => 12,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            $details = [
                'leave' => ['leave_category_id' => $category->id],
                'late' => ['late_date' => $date->toDateString(), 'reason' => 'Kendala perjalanan'],
                'overtime' => [
                    'overtime_date' => $date->toDateString(),
                    'start_time' => '18:00', 'end_time' => '19:00', 'total_minutes' => 60,
                    'reason' => 'Penyelesaian pekerjaan',
                ],
            ];

            foreach ($details as $type => $detail) {
                $request = EmployeeRequest::factory()->for($employee)->create(['request_type' => $type]);
                DB::table($type.'_request_details')->insert([
                    ...$detail, 'request_id' => $request->id, 'created_at' => now(), 'updated_at' => now(),
                ]);

                if ($type === 'leave') {
                    DB::table('leave_request_days')->insert([
                        'leave_request_detail_id' => $request->id, 'employee_leave_entitlement_id' => $entitlementId,
                        'leave_date' => $date->toDateString(), 'duration_days' => 1,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }

            $actor = User::factory()->create(['name' => 'Seeder jadwal', 'status' => 'inactive']);
            $workDate = $date->toDateString();
            $schedules->saveOverride($employee, new EmployeeShiftSchedule, [
                'work_date' => $workDate, 'work_shift_id' => $shift->id, 'status' => 'scheduled',
            ], $actor->id, null);
            $schedules->saveOverride($partner, new EmployeeShiftSchedule, [
                'work_date' => $workDate, 'work_shift_id' => $partnerShift->id, 'status' => 'scheduled',
            ], $actor->id, null);
            $shifts->submitSwap([
                'employee_code' => $employee->employee_code, 'target_employee_code' => $partner->employee_code,
                'requester_work_date' => $workDate, 'target_work_date' => $workDate,
            ]);
        });
    }
}
