<?php

namespace App\Employee\Http\Requests;

use App\Employee\Models\EmployeeEmploymentPeriod;
use Illuminate\Validation\Rule;

class SaveEmploymentPeriodRequest extends EmployeeDetailsRequest
{
    public function rules(): array
    {
        return [
            'employment_type' => ['required', Rule::in(array_keys(EmployeeEmploymentPeriod::TYPES))],
            'status' => ['required', Rule::in(array_keys(EmployeeEmploymentPeriod::STATUSES))],
            'effective_start_date' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'effective_end_date' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:effective_start_date'],
            'reason' => ['nullable', 'string', 'max:2000'],
            ...($this->route('period') ? ['edit_version' => ['required', 'string', 'size:64']] : []),
        ];
    }

    public function attributes(): array
    {
        return [
            'employment_type' => 'jenis hubungan kerja',
            'effective_start_date' => 'tanggal mulai',
            'effective_end_date' => 'tanggal akhir',
            'reason' => 'alasan',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->route('period')
            ? route('admin.employees.periods.edit', [$this->route('employee'), $this->route('period')])
            : route('admin.employees.periods.create', $this->route('employee'));
    }
}
