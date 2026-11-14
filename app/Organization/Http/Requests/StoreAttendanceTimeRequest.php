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
            'check_in_start_day' => ['required', 'integer', 'between:-1,0'],
            'expected_check_in' => ['required', 'date_format:H:i'],
            'expected_check_out' => ['required', 'date_format:H:i'],
            'expected_check_out_day' => ['required', 'integer', 'between:0,1'],
            'check_out_end' => ['nullable', 'date_format:H:i'],
            'check_out_end_day' => ['required', 'integer', 'between:0,1'],
            'late_tolerance_minutes' => ['required', 'integer', 'between:0,1439'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('check_out_end')) {
            $this->merge(['check_out_end' => null, 'check_out_end_day' => 0]);
        }
    }

    public function messages(): array
    {
        return ['name.unique' => 'Nama waktu absen ini sudah dipakai.'];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny([
                    'check_in_start', 'check_in_start_day', 'expected_check_in',
                    'expected_check_out', 'expected_check_out_day', 'check_out_end', 'check_out_end_day',
                ])) {
                    return;
                }

                $checkIn = $this->string('expected_check_in')->toString();
                $checkOut = [$this->integer('expected_check_out_day'), $this->input('expected_check_out')];
                $end = $this->input('check_out_end');

                if ([$this->integer('check_in_start_day'), $this->input('check_in_start')] > [0, $checkIn]) {
                    $validator->errors()->add('check_in_start', 'Waktu mulai absen masuk harus sebelum atau sama dengan waktu masuk. Periksa pilihan hari.');
                }

                if ($checkOut <= [0, $checkIn] || $checkOut >= [1, $checkIn]) {
                    $validator->errors()->add('expected_check_out', 'Waktu pulang harus setelah waktu masuk dengan durasi kurang dari 24 jam. Periksa pilihan hari.');
                }

                if ($end !== null && [$this->integer('check_out_end_day'), $end] < $checkOut) {
                    $validator->errors()->add('check_out_end', 'Batas absen pulang harus sama dengan atau sesudah waktu pulang. Periksa pilihan hari.');
                }
            },
        ];
    }
}
