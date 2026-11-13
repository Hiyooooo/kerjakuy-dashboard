<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['code', 'name', 'address', 'latitude', 'longitude', 'is_active'])]
class Office extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
