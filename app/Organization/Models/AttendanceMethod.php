<?php

namespace App\Organization\Models;

use Database\Factories\AttendanceMethodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'requires_location', 'requires_face_verification', 'requires_registered_device', 'is_active'])]
#[UseFactory(AttendanceMethodFactory::class)]
class AttendanceMethod extends Model
{
    /** @use HasFactory<AttendanceMethodFactory> */
    use HasFactory;

    public const string DUPLICATE_MESSAGE = 'Nama metode absen ini sudah dipakai.';

    public const array REQUIREMENTS = [
        'requires_location' => 'Lokasi absen',
        'requires_face_verification' => 'Verifikasi wajah',
        'requires_registered_device' => 'Perangkat terdaftar',
    ];

    /** @return Builder<static> */
    public static function matchingName(string $name): Builder
    {
        return static::query()->whereRaw('LOWER(name) = LOWER(?)', [$name]);
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([
            $this->getKey(),
            $this->only($this->getFillable()),
        ]));
    }

    protected function casts(): array
    {
        return [
            'requires_location' => 'boolean',
            'requires_face_verification' => 'boolean',
            'requires_registered_device' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
