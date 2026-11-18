<?php

namespace App\Scheduling\Http\Resources;

use App\Scheduling\Models\AttendanceRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttendanceRecord */
class AttendanceRecordResource extends JsonResource
{
    /** @return array<string, int|string|null> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'work_date' => $this->work_date->toDateString(),
            'scheduled_check_in_at' => $this->scheduled_check_in_at->toIso8601String(),
            'scheduled_check_out_at' => $this->scheduled_check_out_at->toIso8601String(),
            'check_in_at' => $this->check_in_at?->toIso8601String(),
            'check_out_at' => $this->check_out_at?->toIso8601String(),
            'status' => $this->status,
            'late_minutes' => $this->late_minutes,
        ];
    }
}
