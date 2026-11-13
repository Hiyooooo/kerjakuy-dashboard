<?php

namespace App\Http\Requests;

class UpdatePermissionsRequest extends StorePermissionsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return parent::authorize() && ! $this->route('permission')->isProtected();
    }
}
