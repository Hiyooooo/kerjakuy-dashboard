<?php

namespace App\Employee\Http\Requests;

use App\Employee\EmployeeHistory;
use Illuminate\Validation\Rule;

class SaveEmployeeHistoryRequest extends EmployeeDetailsRequest
{
    public function history(): EmployeeHistory
    {
        return EmployeeHistory::from($this->route('history'));
    }

    public function rules(): array
    {
        $history = $this->history();
        $rules = [];

        foreach ($history->fields() as $field => [, $type, $length, $required]) {
            $rules[$field] = [
                'bail',
                $required ? 'required' : 'nullable',
                ...match ($type) {
                    'date' => ['date_format:Y-m-d', 'after_or_equal:0001-01-01', 'before_or_equal:9999-12-31'],
                    'number' => ['integer', 'between:1,9999'],
                    default => ['string', 'max:'.$length],
                },
            ];
        }

        if ($history === EmployeeHistory::Education && $this->filled('start_year')) {
            $rules['graduation_year'][] = 'gte:start_year';
        }

        if ($history === EmployeeHistory::Training) {
            $rules['certificate'] = ['nullable', Rule::prohibitedIf($this->boolean('remove_certificate')), 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            if ($this->route('record') !== null) {
                $rules['remove_certificate'] = ['sometimes', 'boolean'];
            }
            if ($this->filled('start_date')) {
                $rules['end_date'][] = 'after_or_equal:start_date';
            }
        }

        if ($this->route('record') !== null) {
            $rules['edit_version'] = ['required', 'string', 'size:64'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        $attributes = ['certificate' => 'file sertifikat'];
        foreach ($this->history()->fields() as $field => [$label]) {
            $attributes[$field] = mb_strtolower($label);
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'graduation_year.gte' => 'Tahun lulus tidak boleh sebelum tahun mulai.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'certificate.mimes' => 'Pilih sertifikat berformat PDF, JPG, atau PNG.',
            'certificate.max' => 'Ukuran sertifikat maksimal 5 MB.',
            'certificate.prohibited' => 'Pilih hapus sertifikat atau unggah pengganti.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $history = $this->history();
        $employee = $this->route('employee');
        $record = $this->route('record');

        return $record === null
            ? route($history->routeName('create'), $employee)
            : route($history->routeName('edit'), [$employee, $record]);
    }
}
