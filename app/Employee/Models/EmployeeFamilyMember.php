<?php

namespace App\Employee\Models;

use Database\Factories\Employee\Models\EmployeeFamilyMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['full_name', 'relationship', 'birth_date', 'occupation', 'phone', 'is_tax_dependent', 'tax_dependent_start_date', 'tax_dependent_end_date'])]
#[UseFactory(EmployeeFamilyMemberFactory::class)]
class EmployeeFamilyMember extends Model
{
    /** @use HasFactory<EmployeeFamilyMemberFactory> */
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }

    protected function casts(): array
    {
        return [
            'birth_date' => 'immutable_date',
            'is_tax_dependent' => 'boolean',
            'tax_dependent_start_date' => 'immutable_date',
            'tax_dependent_end_date' => 'immutable_date',
        ];
    }
}
