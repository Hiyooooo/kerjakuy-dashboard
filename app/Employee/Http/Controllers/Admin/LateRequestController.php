<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\StoreLateRequest;
use App\Employee\Models\EmployeeRequest;
use App\Employee\Services\LateRequestService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LateRequestController extends Controller
{
    public function index(): View
    {
        $requests = EmployeeRequest::with('employee:id,full_name,employee_code')
            ->join('late_request_details as detail', 'detail.request_id', '=', 'requests.id')
            ->where('requests.request_type', 'late')->latest('requests.id')
            ->paginate(20, ['requests.*', 'detail.late_date']);

        return view('admin.employees.late-requests.index', compact('requests'));
    }

    public function create(): View
    {
        return view('admin.employees.late-requests.form');
    }

    public function store(StoreLateRequest $request, LateRequestService $service): RedirectResponse
    {
        $late = $service->submit($request->validated());

        return to_route('admin.late-requests.show', $late)->with('late_status', 'Permohonan terlambat tercatat.');
    }

    public function show(EmployeeRequest $lateRequest): View
    {
        $detail = $this->detail($lateRequest);

        return view('admin.employees.late-requests.show', compact('lateRequest', 'detail'));
    }

    public function attachment(EmployeeRequest $lateRequest): StreamedResponse
    {
        $path = $this->detail($lateRequest)->attachment_path;
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->download($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function detail(EmployeeRequest $lateRequest): object
    {
        abort_unless($lateRequest->request_type === 'late', 404);
        $detail = DB::table('late_request_details')->where('request_id', $lateRequest->id)->first();
        abort_if($detail === null, 404);

        return $detail;
    }
}
