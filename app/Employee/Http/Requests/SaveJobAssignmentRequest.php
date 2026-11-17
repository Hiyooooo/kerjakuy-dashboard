<?php

namespace App\Employee\Http\Requests;

use App\Organization\Models\Office;
use App\Organization\Models\Position;
use Illuminate\Validation\Rule;

class SaveJobAssignmentRequest extends EmployeeDetailsRequest
{
    public function rules(): array
    {
        return [
            'office_id' => ['bail', 'required', 'integer', Rule::exists(Office::class, 'id')],
            'position_id' => ['bail', 'required', 'integer', Rule::exists(Position::class, 'id')],
            'change_type' => ['required', 'string', 'max:50'],
            'effective_start_date' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'effective_end_date' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:effective_start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            ...($this->route('assignment') ? ['edit_version' => ['required', 'string', 'size:64']] : []),
        ];
    }

    public function attributes(): array
    {
        return [
            'office_id' => 'kantor',
            'position_id' => 'jabatan',
            'change_type' => 'jenis perubahan',
            'effective_start_date' => 'tanggal mulai',
            'effective_end_date' => 'tanggal akhir',
            'notes' => 'catatan',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->route('assignment')
            ? route('admin.employees.assignments.edit', [$this->route('employee'), $this->route('assignment')])
            : route('admin.employees.assignments.create', $this->route('employee'));
    }
}
