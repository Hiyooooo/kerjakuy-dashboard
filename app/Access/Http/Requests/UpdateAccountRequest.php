<?php

namespace App\Access\Http\Requests;

class UpdateAccountRequest extends StoreAccountRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('admin.accounts.change', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return $this->rulesFor($this->route('user'));
    }
}
