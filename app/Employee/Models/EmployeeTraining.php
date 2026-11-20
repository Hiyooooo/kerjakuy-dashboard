<?php

namespace App\Employee\Models;

use Database\Factories\Employee\Models\EmployeeTrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'provider', 'start_date', 'end_date', 'certificate_number', 'notes'])]
#[Hidden(['certificate_file_path'])]
#[UseFactory(EmployeeTrainingFactory::class)]
class EmployeeTraining extends Model
{
    /** @use HasFactory<EmployeeTrainingFactory> */
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected function casts(): array
    {
        return ['start_date' => 'immutable_date', 'end_date' => 'immutable_date'];
    }
}
