<?php

namespace App\Organization\Http\Requests;

use App\Organization\Models\AttendanceLocation;
use Illuminate\Foundation\Http\FormRequest;

class SaveAttendanceLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        return [
            'office_id' => ['nullable', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:120'],
            'latitude' => ['bail', 'required', 'numeric', 'between:-90,90', 'decimal:0,7'],
            'longitude' => ['bail', 'required', 'numeric', 'between:-180,180', 'decimal:0,7'],
            'radius_meter' => ['bail', 'required', 'integer', 'between:1,'.AttendanceLocation::MAX_RADIUS_METER],
            'is_active' => ['required', 'boolean'],
            ...($this->route('attendance_location') ? ['edit_version' => ['required', 'string', 'size:64']] : []),
        ];
    }

    public function attributes(): array
    {
        return [
            'office_id' => 'kantor',
            'name' => 'nama lokasi',
            'latitude' => 'lintang',
            'longitude' => 'bujur',
            'radius_meter' => 'radius',
            'is_active' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.*' => 'Masukkan lintang antara −90 dan 90 dengan maksimal tujuh angka desimal.',
            'longitude.*' => 'Masukkan bujur antara −180 dan 180 dengan maksimal tujuh angka desimal.',
            'radius_meter.*' => 'Masukkan radius sebagai bilangan bulat antara 1 dan '.number_format(AttendanceLocation::MAX_RADIUS_METER, 0, ',', '.').' meter.',
            'edit_version.*' => 'Buka versi terbaru sebelum menyimpan kembali.',
        ];
    }
}
