<?php

namespace App\Organization\Http\Requests;

use App\Organization\Models\JobDesk;
use App\Organization\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJobDeskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        $record = $this->route('job_desk');
        $uniqueTitle = Rule::unique(JobDesk::class, 'title')->where('position_id', $this->integer('position_id'));

        if ($record instanceof JobDesk) {
            $uniqueTitle->ignore($record);
        }

        return [
            'position_id' => ['bail', 'required', 'integer', Rule::exists(Position::class, 'id')],
            'title' => ['required', 'string', 'max:150', $uniqueTitle],
            'job_description' => ['nullable', 'string', 'max:10000'],
            'job_specification' => ['nullable', 'string', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['title.unique' => 'Judul ini sudah dipakai pada jabatan yang sama.'];
    }
}
