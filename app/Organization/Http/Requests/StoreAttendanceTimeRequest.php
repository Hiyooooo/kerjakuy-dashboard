<?php

namespace App\Organization\Http\Requests;

use App\Organization\Models\AttendanceTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAttendanceTimeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        $record = $this->route('attendance_time');
        $uniqueName = Rule::unique(AttendanceTime::class, 'name');

        if ($record instanceof AttendanceTime) {
            $uniqueName->ignore($record);
        }

        return [
            'name' => ['required', 'string', 'max:100', $uniqueName],
            'check_in_start' => ['required', 'date_format:H:i'],
            'expected_check_in' => ['required', 'date_format:H:i'],
            'expected_check_out' => ['required', 'date_format:H:i'],
            'check_out_end' => ['nullable', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'between:0,1439'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['check_in_start', 'expected_check_in', 'expected_check_out', 'check_out_end'])) {
                    return;
                }

                $checkIn = $this->string('expected_check_in')->toString();
                $checkOut = $this->string('expected_check_out')->toString();
                $end = $this->input('check_out_end');

                if ($this->string('check_in_start')->toString() > $checkIn) {
                    $validator->errors()->add('check_in_start', 'Jam mulai absen masuk harus sebelum atau sama dengan jam masuk.');
                }

                if ($checkOut === $checkIn) {
                    $validator->errors()->add('expected_check_out', 'Jam pulang harus berbeda dari jam masuk.');
                }

                $checkOutDay = (int) ($checkOut < $checkIn);
                $endDay = (int) ($end !== null && $end < $checkIn);

                if ($end !== null && [$endDay, $end] < [$checkOutDay, $checkOut]) {
                    $validator->errors()->add('check_out_end', 'Batas absen pulang harus sama dengan atau sesudah jam pulang.');
                }
            },
        ];
    }
}
