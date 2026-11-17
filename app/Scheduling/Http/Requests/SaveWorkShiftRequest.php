<?php

namespace App\Scheduling\Http\Requests;

use App\Organization\Models\AttendanceTime;
use App\Scheduling\Models\WorkShift;
use Illuminate\Validation\Rule;

class SaveWorkShiftRequest extends SaveSchedulingRequest
{
    public function rules(): array
    {
        $shift = $this->route('work_shift');
        $code = Rule::unique(WorkShift::class);
        if ($shift instanceof WorkShift) {
            $code->ignore($shift);
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['bail', 'required', 'string', 'max:30', $code],
            'attendance_time_id' => ['bail', 'required', 'integer', Rule::exists(AttendanceTime::class, 'id')],
            'is_active' => ['required', 'boolean'],
            ...($shift instanceof WorkShift ? ['edit_version' => ['required', 'string', 'size:64']] : []),
        ];
    }
}
