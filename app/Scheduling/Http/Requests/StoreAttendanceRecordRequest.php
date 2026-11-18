<?php

namespace App\Scheduling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.attendance.manage') === true;
    }

    public function rules(): array
    {
        return [
            'recorded_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'selfie' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'reason_id' => ['nullable', 'integer', 'exists:out_of_location_reasons,id'],
            'location_note' => ['nullable', 'string', 'max:2000'],
            ...($this->routeIs('*.store') ? [
                'employee_code' => ['required', 'string', 'max:30'],
                'work_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
                'method_id' => ['required', 'integer', 'exists:attendance_methods,id'],
                'location_id' => ['nullable', 'integer', 'exists:attendance_locations,id'],
                'device_id' => ['nullable', 'integer', 'exists:registered_devices,id'],
            ] : []),
        ];
    }

    public function attributes(): array
    {
        return [
            'recorded_at' => 'waktu absensi',
            'employee_code' => 'kode pegawai',
            'work_date' => 'tanggal kerja',
            'method_id' => 'metode absensi',
            'location_id' => 'lokasi acuan',
            'device_id' => 'perangkat',
            'latitude' => 'lintang',
            'longitude' => 'bujur',
            'selfie' => 'foto selfie',
            'reason_id' => 'alasan di luar lokasi',
            'location_note' => 'catatan di luar lokasi',
        ];
    }
}
