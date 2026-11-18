<?php

namespace App\Scheduling\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Scheduling\Http\Resources\AttendanceRecordResource;
use App\Scheduling\Models\AttendanceRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $records = $this->recordsFor($request)
            ->orderByDesc('work_date')->orderByDesc('id')->paginate(20);

        return AttendanceRecordResource::collection($records)->response()
            ->header('Cache-Control', 'private, no-store');
    }

    public function show(Request $request, string $recordId): JsonResponse
    {
        $id = filter_var($recordId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($id === false, 404);

        $record = $this->recordsFor($request)->findOrFail($id);

        return (new AttendanceRecordResource($record))->response()
            ->header('Cache-Control', 'private, no-store');
    }

    public function unavailable(): JsonResponse
    {
        return response()->json([
            'code' => 'attendance_submission_unavailable',
            'message' => 'Pengiriman absensi dari aplikasi belum tersedia.',
        ], 503, ['Cache-Control' => 'private, no-store']);
    }

    /** @return Builder<AttendanceRecord> */
    private function recordsFor(Request $request): Builder
    {
        return AttendanceRecord::query()->where('employee_id', $request->user()->employee_id);
    }
}
