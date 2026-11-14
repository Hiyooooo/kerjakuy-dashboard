<?php

namespace App\Organization\Models;

use Database\Factories\AttendanceTimeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'check_in_start', 'expected_check_in', 'expected_check_out', 'check_out_end', 'late_tolerance_minutes', 'is_active'])]
#[UseFactory(AttendanceTimeFactory::class)]
class AttendanceTime extends Model
{
    /** @use HasFactory<AttendanceTimeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'late_tolerance_minutes' => 'integer'];
    }
}
