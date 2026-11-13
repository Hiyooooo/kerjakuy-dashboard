<?php

namespace App\Http\Requests;

class UpdateRoleRequest extends StoreRoleRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return parent::authorize() && ! $this->route('role')->isSystemRole();
    }
}
