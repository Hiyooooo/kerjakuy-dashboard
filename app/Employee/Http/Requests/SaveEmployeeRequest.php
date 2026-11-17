<?php

namespace App\Employee\Http\Requests;

use App\Employee\Models\Employee;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaveEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.employees.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['employee_code', 'email'] as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $this->merge([$field => $field === 'email' ? Str::lower($value) : Str::upper($value)]);
            }
        }
    }

    public function rules(): array
    {
        $employee = $this->route('employee');
        $code = Rule::unique(Employee::class, 'employee_code');
        $email = Rule::unique(Employee::class, 'email');

        if ($employee instanceof Employee) {
            $code->ignore($employee);
            $email->ignore($employee);
        }

        return [
            'employee_code' => ['bail', 'required', 'string', 'max:30', $code],
            'full_name' => ['required', 'string', 'max:150'],
            'email' => ['bail', 'nullable', 'string', 'max:150', 'email', $email],
            'phone' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:0001-01-01', 'before_or_equal:'.today('Asia/Jakarta')->toDateString()],
            'address' => ['nullable', 'string', 'max:2000'],
            ...($employee instanceof Employee ? ['edit_version' => ['required', 'string', 'size:64']] : []),
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_code' => 'kode pegawai',
            'full_name' => 'nama lengkap',
            'email' => 'email',
            'phone' => 'nomor telepon',
            'birth_date' => 'tanggal lahir',
            'address' => 'alamat',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw $this->forEmployeeForm(new ValidationException($validator));
    }

    public function forEmployeeForm(ValidationException $exception): ValidationException
    {
        if ($this->expectsJson()) {
            return $exception;
        }

        $employee = $this->route('employee');
        $form = 'employee_'.($employee?->getKey() ?? 'create');
        $redirect = $employee
            ? to_route('admin.employees.edit', $employee)
            : to_route('admin.employees.create');

        $exception->response = $redirect
            ->withInput([
                ...$this->only(['employee_code', 'full_name', 'email', 'phone', 'birth_date', 'address', 'edit_version']),
                '_employee_form' => $form,
            ])
            ->withErrors($exception->errors(), $form);

        return $exception;
    }

    public function messages(): array
    {
        return [
            'employee_code.unique' => 'Kode pegawai sudah digunakan.',
            'email.unique' => 'Email sudah digunakan.',
            'birth_date.before_or_equal' => 'Tanggal lahir tidak boleh setelah hari ini.',
            'edit_version.*' => 'Buka versi terbaru sebelum menyimpan kembali.',
        ];
    }
}
