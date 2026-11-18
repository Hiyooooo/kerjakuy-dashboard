<?php

namespace App\Scheduling\Http\Requests;

use App\Organization\Models\Office;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use Illuminate\Validation\Rule;

class SaveEmployeeScheduleRequest extends SaveSchedulingRequest
{
    public function rules(): array
    {
        $version = $this->routeIs('*.update') ? ['edit_version' => ['required', 'string', 'size:64']] : [];

        if ($this->routeIs('admin.employees.schedule-assignments.*')) {
            return [
                'work_schedule_template_id' => ['bail', 'required', 'integer', Rule::exists(WorkScheduleTemplate::class, 'id')],
                'effective_start_date' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
                'effective_end_date' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:effective_start_date'],
                ...$version,
            ];
        }

        return [
            'work_shift_id' => ['bail', 'required', 'integer', Rule::exists(WorkShift::class, 'id')],
            'office_id' => ['bail', 'nullable', 'integer', Rule::exists(Office::class, 'id')],
            'work_date' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'status' => ['required', Rule::in(['scheduled', 'cancelled'])],
            'reason' => ['nullable', 'string', 'max:2000'],
            ...$version,
        ];
    }

    public function attributes(): array
    {
        return [
            'work_schedule_template_id' => 'template jadwal',
            'effective_start_date' => 'tanggal mulai',
            'effective_end_date' => 'tanggal akhir',
            'work_shift_id' => 'shift khusus',
            'work_date' => 'tanggal kerja',
            'office_id' => 'kantor',
        ];
    }
}
