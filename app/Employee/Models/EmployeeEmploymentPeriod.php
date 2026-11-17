<?php

namespace App\Employee\Models;

use Database\Factories\EmployeeEmploymentPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['employment_type', 'status', 'effective_start_date', 'effective_end_date', 'reason'])]
#[UseFactory(EmployeeEmploymentPeriodFactory::class)]
class EmployeeEmploymentPeriod extends Model
{
    /** @use HasFactory<EmployeeEmploymentPeriodFactory> */
    use HasFactory;

    public const array TYPES = [
        'permanent' => 'Tetap',
        'contract' => 'Kontrak',
        'intern' => 'Magang',
        'freelance' => 'Lepas',
    ];

    public const array STATUSES = [
        'active' => 'Aktif',
        'inactive' => 'Nonaktif',
        'resigned' => 'Mengundurkan diri',
    ];

    protected function casts(): array
    {
        return ['effective_start_date' => 'immutable_date', 'effective_end_date' => 'immutable_date'];
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }
}
