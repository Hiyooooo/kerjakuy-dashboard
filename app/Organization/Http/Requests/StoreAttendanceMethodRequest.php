<?php

namespace App\Organization\Http\Requests;

use App\Organization\Models\AttendanceMethod;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:100', function (string $attribute, string $value, Closure $fail): void {
                $record = $this->route('attendance_method');
                $query = AttendanceMethod::matchingName($value);

                if ($record instanceof AttendanceMethod) {
                    $query->whereKeyNot($record);
                }

                if ($query->exists()) {
                    $fail(AttendanceMethod::DUPLICATE_MESSAGE);
                }
            }],
            'requires_location' => ['required', 'boolean'],
            'requires_face_verification' => ['required', 'boolean'],
            'requires_registered_device' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
