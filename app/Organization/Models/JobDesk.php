<?php

namespace App\Organization\Models;

use Database\Factories\JobDeskFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['position_id', 'title', 'job_description', 'job_specification', 'is_active'])]
#[UseFactory(JobDeskFactory::class)]
class JobDesk extends Model
{
    /** @use HasFactory<JobDeskFactory> */
    use HasFactory;

    /** @return BelongsTo<Position, $this> */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
