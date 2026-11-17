<?php

namespace App\Employee\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

abstract class EmployeeDetailsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.employees.manage') === true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw $this->forForm(new ValidationException($validator));
    }

    public function forForm(ValidationException $exception): ValidationException
    {
        if (! $this->expectsJson()) {
            $url = $this->getRedirectUrl();
            $input = $this->only(array_diff(array_keys($this->rules()), ['national_id']));

            $exception->response = redirect($url)
                ->withInput(array_filter($input, fn (mixed $value): bool => is_string($value) || $value === null))
                ->withErrors($exception->errors(), $url);
        }

        return $exception;
    }
}
