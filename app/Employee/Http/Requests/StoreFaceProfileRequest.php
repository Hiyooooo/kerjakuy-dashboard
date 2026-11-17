<?php

namespace App\Employee\Http\Requests;

class StoreFaceProfileRequest extends EmployeeDetailsRequest
{
    public function rules(): array
    {
        return [
            'active_profile_id' => ['required', 'integer', 'min:0'],
            'photo' => ['bail', 'required', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'active_profile_id.*' => 'Buka versi terbaru sebelum mengunggah kembali.',
            'photo.*' => 'Pilih foto JPG atau PNG hingga 5 MB dengan ukuran maksimal 4.096 × 4.096 piksel.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.employees.face-profiles.index', $this->route('employee'));
    }
}
