<?php

namespace App\Organization\Http\Requests;

use App\Organization\Models\Holiday;
use App\Organization\Models\Office;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.organization.manage') === true;
    }

    public function rules(): array
    {
        $officeId = $this->route('holiday')?->office_id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'office_id' => [
                'bail',
                'required',
                Rule::when($this->input('office_id') === 'all', ['in:all'], [
                    'integer',
                    Rule::exists(Office::class, 'id')->using(fn (Builder $query): Builder => $query->where(
                        fn (Builder $office): Builder => $office->where('is_active', true)->orWhere('id', $officeId)
                    )),
                ]),
            ],
            'holiday_date' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'office_id.required' => 'Pilih semua kantor atau satu kantor.',
            'office_id.exists' => 'Kantor yang dipilih sudah tidak tersedia atau nonaktif. Pilih ulang cakupan hari libur.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['office_id', 'holiday_date', 'name'])) {
                    return;
                }

                $record = $this->route('holiday');
                $duplicate = Holiday::where('office_id', $this->selectedOfficeId())
                    ->whereDate('holiday_date', $this->input('holiday_date'))
                    ->where('name', $this->input('name'))
                    ->when($record instanceof Holiday, fn ($query) => $query->whereKeyNot($record))
                    ->exists();

                if ($duplicate) {
                    $validator->errors()->add('name', Holiday::DUPLICATE_MESSAGE);
                }
            },
        ];
    }

    /** @return array{name: string, office_id: int|null, holiday_date: string, description?: string|null} */
    public function holidayData(): array
    {
        return [...$this->validated(), 'office_id' => $this->selectedOfficeId()];
    }

    private function selectedOfficeId(): ?int
    {
        return $this->input('office_id') === 'all' ? null : $this->integer('office_id');
    }
}
