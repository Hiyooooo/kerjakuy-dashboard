<?php

namespace App\Organization\Models;

use Database\Factories\FaceTolerancePolicyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['minimum_match_score', 'effective_start_date', 'effective_end_date'])]
#[UseFactory(FaceTolerancePolicyFactory::class)]
class FaceTolerancePolicy extends Model
{
    /** @use HasFactory<FaceTolerancePolicyFactory> */
    use HasFactory;

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->only($this->getFillable())]));
    }

    protected function casts(): array
    {
        return [
            'minimum_match_score' => 'decimal:2',
            'effective_start_date' => 'immutable_date',
            'effective_end_date' => 'immutable_date',
        ];
    }
}
