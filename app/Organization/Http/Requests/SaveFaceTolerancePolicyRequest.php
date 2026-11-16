<?php

namespace App\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveFaceTolerancePolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        $rules = [
            'minimum_match_score' => ['bail', 'required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'effective_start_date' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'effective_end_date' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:effective_start_date'],
        ];

        if ($this->route('face_tolerance_policy') !== null) {
            $rules['edit_version'] = ['required', 'string', 'size:64'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'minimum_match_score.*' => 'Masukkan skor 0 sampai 100 dengan maksimal dua angka desimal.',
            'effective_start_date.*' => 'Masukkan tanggal mulai yang valid.',
            'effective_end_date.date_format' => 'Masukkan tanggal akhir yang valid.',
            'effective_end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
            'edit_version.*' => 'Buka versi terbaru sebelum menyimpan kembali.',
        ];
    }
}
