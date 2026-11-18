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
        $formRoute = $resource.($this->routeIs('*.update') ? '.edit' : '.create');
        $parameters = $this->route()->parameters();
        $token = (string) Str::uuid();

        $exception->response = to_route($formRoute, [...$parameters, 'form' => $token])
            ->withInput([
                ...$this->only(array_keys($this->rules())),
                '_scheduling_form' => route($formRoute, $parameters),
                '_scheduling_token' => $token,
            ])->withErrors($exception->errors());

        return $exception;
    }
}
