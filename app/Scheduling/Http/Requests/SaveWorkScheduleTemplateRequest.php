<?php

namespace App\Scheduling\Http\Requests;

use App\Organization\Models\Office;
use App\Scheduling\Models\WorkScheduleTemplate;
use Illuminate\Validation\Rule;

class SaveWorkScheduleTemplateRequest extends SaveSchedulingRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'office_id' => ['bail', 'nullable', 'integer', Rule::exists(Office::class, 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'days' => ['required', 'array:1,2,3,4,5,6,7'],
            ...($this->route('work_schedule_template') ? ['edit_version' => ['required', 'string', 'size:64']] : []),
        ];

        foreach (WorkScheduleTemplate::WEEKDAYS as $number => $day) {
            $rules["days.$number"] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }
}
