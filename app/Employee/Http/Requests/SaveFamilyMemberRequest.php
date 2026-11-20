<?php

namespace App\Employee\Http\Requests;

class SaveFamilyMemberRequest extends EmployeeDetailsRequest
{
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'relationship' => ['required', 'string', 'max:50'],
            'birth_date' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:0001-01-01', 'before_or_equal:'.today('Asia/Jakarta')->toDateString()],
            'occupation' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_tax_dependent' => ['required', 'boolean'],
            'tax_dependent_start_date' => ['bail', 'required_if:is_tax_dependent,1', 'prohibited_if:is_tax_dependent,0', 'nullable',
                'date_format:Y-m-d', 'after_or_equal:0001-01-01', ...($this->filled('birth_date') ? ['after_or_equal:birth_date'] : [])],
            'tax_dependent_end_date' => ['bail', 'prohibited_if:is_tax_dependent,0', 'nullable', 'date_format:Y-m-d', 'after_or_equal:tax_dependent_start_date'],
            ...($this->route('family_member') ? [
                'edit_version' => ['required', 'string', 'size:64'],
                'start_new_tax_period' => ['sometimes', 'boolean'],
            ] : []),
        ];
    }

    protected function prepareForValidation(): void
    {
        $dependent = $this->input('is_tax_dependent');

        if (is_bool($dependent) || in_array($dependent, [0, 1], true)) {
            $this->merge(['is_tax_dependent' => (string) (int) $dependent]);
        }
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'nama lengkap',
            'relationship' => 'hubungan keluarga',
            'birth_date' => 'tanggal lahir',
            'occupation' => 'pekerjaan',
            'phone' => 'nomor telepon',
            'is_tax_dependent' => 'status tanggungan pajak',
            'tax_dependent_start_date' => 'tanggal mulai tanggungan',
            'tax_dependent_end_date' => 'tanggal akhir tanggungan',
        ];
    }

    public function messages(): array
    {
        return [
            'tax_dependent_start_date.required_if' => 'Isi tanggal mulai untuk tanggungan pajak.',
            'tax_dependent_start_date.prohibited_if' => 'Kosongkan tanggal mulai jika anggota keluarga bukan tanggungan pajak.',
            'tax_dependent_end_date.prohibited_if' => 'Kosongkan tanggal akhir jika anggota keluarga bukan tanggungan pajak.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->route('family_member')
            ? route('admin.employees.family-members.edit', [$this->route('employee'), $this->route('family_member')])
            : route('admin.employees.family-members.create', $this->route('employee'));
    }
}
