<?php

namespace App\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.employees.manage') === true;
    }

    public function rules(): array
    {
        return [
            'employee_code' => ['required', 'string', 'max:30'],
            'leave_category_id' => ['required', 'integer', 'exists:leave_categories,id'],
            ...($this->routeIs('*.entitlements.store') ? [
                'entitlement_year' => ['required', 'integer', 'between:1,9999'],
                'granted_days' => ['required', 'numeric', 'between:0,999.99', 'decimal:0,2'],
                'carry_over_days' => ['nullable', 'numeric', 'between:0,999.99', 'decimal:0,2'],
            ] : [
                'days' => ['required', 'array', 'max:366'],
                'days.*' => ['required', 'array:date,duration'],
                'days.*.date' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01', 'distinct'],
                'days.*.duration' => ['required', 'numeric', 'between:0.01,1', 'decimal:0,2'],
                'reason' => ['nullable', 'string', 'max:2000'],
                'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ]),
        ];
    }

    protected function prepareForValidation(): void
    {
        $days = $this->input('days');
        if (is_array($days)) {
            $this->merge(['days' => array_values($days)]);
        }
    }

    public function attributes(): array
    {
        return [
            'employee_code' => 'kode pegawai',
            'leave_category_id' => 'kategori cuti',
            'entitlement_year' => 'tahun jatah',
            'granted_days' => 'jatah cuti',
            'carry_over_days' => 'sisa bawaan',
            'days.*.date' => 'tanggal cuti',
            'days.*.duration' => 'durasi cuti',
            'attachment' => 'lampiran',
        ];
    }
}
