<?php

namespace App\Scheduling\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

abstract class SaveSchedulingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.scheduling.manage') === true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw $this->forForm(new ValidationException($validator));
    }

    public function forForm(ValidationException $exception): ValidationException
    {
        if ($this->expectsJson()) {
            return $exception;
        }

        $resource = Str::beforeLast($this->route()->getName(), '.');
        $record = $this->route('work_shift') ?? $this->route('work_schedule_template');
        $formRoute = $resource.($record ? '.edit' : '.create');
        $parameters = $record ? [$record] : [];
        $token = (string) Str::uuid();

        $exception->response = to_route($formRoute, [...$parameters, 'form' => $token])
            ->withInput([
                ...$this->only(['name', 'code', 'attendance_time_id', 'is_active', 'office_id', 'description', 'days', 'edit_version']),
                '_scheduling_form' => route($formRoute, $parameters),
                '_scheduling_token' => $token,
            ])->withErrors($exception->errors());

        return $exception;
    }
}
