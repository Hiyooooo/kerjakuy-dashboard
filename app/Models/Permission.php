<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'description'])]
class Permission extends Model
{
    public const BUILT_IN = ['admin.dashboard.view', 'admin.accounts.manage', 'admin.roles.manage', 'admin.permissions.manage'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }

    public function isProtected(): bool
    {
        return in_array($this->name, self::BUILT_IN, true)
            || ($this->roles_count ?? $this->roles()->exists()) > 0;
    }
}
