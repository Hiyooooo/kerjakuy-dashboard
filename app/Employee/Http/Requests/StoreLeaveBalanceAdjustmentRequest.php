<?php

namespace App\Employee\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeaveBalanceAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.employees.manage') === true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'leave_category_id' => ['required', 'integer', 'exists:leave_categories,id'],
            'year' => ['required', 'integer', 'between:1,9999'],
            'adjustment_days' => [
                'bail', 'required', 'numeric', 'decimal:0,2', 'between:-999.99,999.99',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if ((float) $value === 0.0) {
                        $fail('Penyesuaian hari harus lebih besar atau lebih kecil dari nol.');
                    }
                },
            ],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return ['adjustment_days' => 'penyesuaian hari', 'reason' => 'alasan'];
    }
}
