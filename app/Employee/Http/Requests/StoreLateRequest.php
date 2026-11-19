<?php

namespace App\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.employees.manage') === true;
    }

    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:30'],
            'late_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'expected_check_in' => ['nullable', 'date_format:H:i'],
            'actual_check_in' => ['nullable', 'date_format:H:i'],
            'reason' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_code' => 'kode pegawai',
            'late_date' => 'tanggal kerja',
            'expected_check_in' => 'jadwal masuk',
            'actual_check_in' => 'jam masuk aktual',
            'reason' => 'alasan',
            'attachment' => 'lampiran',
        ];
    }
}
