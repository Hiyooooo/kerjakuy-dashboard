<?php

namespace App\Employee\Http\Requests;

use Illuminate\Validation\Rule;

class SaveEmployeeIdentityRequest extends EmployeeDetailsRequest
{
    public function rules(): array
    {
        return [
            'gender' => ['required', Rule::in(['male', 'female'])],
            'edit_version' => ['required', 'string', 'regex:/\A(?:new|[a-f0-9]{64})\z/'],
            'national_id' => ['nullable', 'string', 'regex:/\A[0-9]{16}\z/'],
        ];
    }

    public function messages(): array
    {
        return [
            'gender.*' => 'Pilih jenis kelamin.',
            'edit_version.*' => 'Buka versi terbaru sebelum menyimpan kembali.',
            'national_id.*' => 'Masukkan NIK dengan 16 digit angka.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.employees.identity.edit', $this->route('employee'));
    }
}
