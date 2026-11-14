<?php

namespace App\Access\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'status', 'role_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ACCOUNT_STATUSES = [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'suspended' => 'Ditangguhkan',
        'pending' => 'Menunggu',
    ];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->code === 'super_admin';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
