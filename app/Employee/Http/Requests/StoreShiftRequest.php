<?php

namespace App\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.employees.manage') === true;
    }

    public function rules(): array
    {
        $date = ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'];

        return [
            'employee_code' => ['required', 'string', 'max:30'],
            ...($this->routeIs('admin.overtime-requests.store') ? [
                'overtime_date' => $date,
                'start_time' => ['required', 'date_format:H:i'],
                'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
                'ends_next_day' => ['sometimes', 'boolean'],
                'reason' => ['required', 'string', 'max:2000'],
            ] : [
                'target_employee_code' => ['required', 'string', 'max:30'],
                'requester_work_date' => $date,
                'target_work_date' => $date,
                'reason' => ['nullable', 'string', 'max:2000'],
            ]),
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_code' => 'kode pegawai',
            'target_employee_code' => 'kode rekan tukar shift',
            'requester_work_date' => 'tanggal kerja pemohon',
            'target_work_date' => 'tanggal kerja rekan',
            'overtime_date' => 'tanggal lembur',
            'start_time' => 'jam mulai',
            'end_time' => 'jam selesai',
            'reason' => 'alasan',
        ];
    }
}
