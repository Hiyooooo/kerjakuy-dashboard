<?php

namespace App\Scheduling\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Scheduling\Models\FaceRecognitionLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FaceRecognitionLogController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');
        $status = in_array($status, ['passed', 'failed'], true) ? $status : null;
        $logs = FaceRecognitionLog::with('employee')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('captured_at')->orderByDesc('id')->paginate(20)->appends(['status' => $status]);

        return response()->view('admin.scheduling.attendance.face-logs', compact('logs', 'status'), 200, ['Cache-Control' => 'private, no-store']);
    }

    public function photo(FaceRecognitionLog $faceRecognitionLog): StreamedResponse
    {
        $path = $faceRecognitionLog->captured_photo_path;
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
