<?php

namespace App\Organization\Http\Requests;

class UpdateAttendanceMethodRequest extends StoreAttendanceMethodRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'edit_version' => ['required', 'string', 'size:64'],
        ];
    }
}
