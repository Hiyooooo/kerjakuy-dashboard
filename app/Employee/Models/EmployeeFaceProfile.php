<?php

namespace App\Employee\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['reference_photo_path', 'reference_photo_hash', 'uploaded_by_user_id', 'uploaded_at'])]
class EmployeeFaceProfile extends Model
{
    protected $hidden = ['reference_photo_path', 'reference_photo_hash'];

    protected function casts(): array
    {
        return ['uploaded_at' => 'immutable_datetime', 'deactivated_at' => 'immutable_datetime'];
    }
}
