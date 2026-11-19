<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\StoreLeaveBalanceAdjustmentRequest;
use App\Employee\Http\Requests\StoreLeaveRequest;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeRequest;
use App\Employee\Services\LeaveRequestService;
use App\Http\Controllers\Controller;
use App\Organization\Models\LeaveCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeaveRequestController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'in:pending,approved,rejected,cancelled'],
        ]);
        $totals = DB::table('leave_request_days')->whereColumn('leave_request_detail_id', 'requests.id')
            ->selectRaw('COALESCE(SUM(duration_days), 0)');
        $requests = EmployeeRequest::query()->with('employee:id,full_name,employee_code')
            ->join('leave_request_details as detail', 'detail.request_id', '=', 'requests.id')
            ->join('leave_categories as category', 'category.id', '=', 'detail.leave_category_id')
            ->where('requests.request_type', 'leave')
            ->select(['requests.*', 'category.name as category'])->selectSub($totals, 'total_days')
            ->withCasts(['total_days' => 'decimal:2'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('requests.status', $status));
        if (($filters['search'] ?? '') !== '') {
            $pattern = '%'.strtr($filters['search'], ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $requests->where(fn ($query) => $query
                ->whereRaw("LOWER(requests.request_number) LIKE LOWER(?) ESCAPE '!'", [$pattern])
                ->orWhereHas('employee', fn ($employee) => $employee->matchingSearch($filters['search'])));
        }

        $page = $requests->latest('requests.id')->paginate(20)->appends($filters);
        $dates = DB::table('leave_request_days')->whereIn('leave_request_detail_id', $page->modelKeys())
            ->orderBy('leave_date')->get(['leave_request_detail_id', 'leave_date'])->groupBy('leave_request_detail_id');
        $page->through(fn (EmployeeRequest $leave) => $leave->setAttribute('leave_dates', $dates->get($leave->id, collect())->pluck('leave_date')->all()));

        return view('admin.employees.leave-requests.index', [
            'requests' => $page,
            'filters' => $filters,
        ]);
    }

    public function employees(Request $request): JsonResponse
    {
        $input = $request->validate(['search' => ['required', 'string', 'max:150']]);
        $employees = Employee::query()->matchingSearch($input['search'])
            ->orderBy('full_name')->orderBy('id')->limit(20)->get(['id', 'full_name', 'employee_code']);

        return response()->json(['data' => $employees]);
    }

    public function balancePage(Request $request, LeaveRequestService $service): View
    {
        $filters = $request->validate([
            'employee_code' => ['required_with:leave_category_id,year', 'nullable', 'string', 'max:30'],
            'leave_category_id' => ['required_with:employee_code,year', 'nullable', 'integer', 'exists:leave_categories,id'],
            'year' => ['required_with:employee_code,leave_category_id', 'nullable', 'integer', 'between:1,9999'],
        ]);
        $employee = null;
        $balance = null;
        if (isset($filters['employee_code'])) {
            $employee = Employee::where('employee_code', Str::upper($filters['employee_code']))->first(['id', 'full_name', 'employee_code']);
            if ($employee === null) {
                throw ValidationException::withMessages(['employee_code' => 'Kode pegawai tidak ditemukan.'])
                    ->redirectTo(route('admin.leave-requests.balances'));
            }
            $year = (int) $filters['year'];
            $balance = $service->balances($employee->id, (int) $filters['leave_category_id'], [$year])->get($year);
        }
        $categories = LeaveCategory::orderBy('name')->get(['id', 'name']);

        return view('admin.employees.leave-requests.balances', compact('filters', 'categories', 'employee', 'balance'));
    }

    public function storeAdjustment(StoreLeaveBalanceAdjustmentRequest $request, LeaveRequestService $service): RedirectResponse
    {
        $data = $request->validated();
        $employee = Employee::query()->findOrFail($data['employee_id'], ['employee_code']);
        $balanceUrl = route('admin.leave-requests.balances', [
            'employee_code' => $employee->employee_code,
            'leave_category_id' => $data['leave_category_id'],
            'year' => $data['year'],
        ]);
        try {
            $service->adjust($data, $request->user()->id);
        } catch (ValidationException $exception) {
            throw $exception->redirectTo($balanceUrl);
        }

        return redirect()->to($balanceUrl)->with('leave_status', 'Penyesuaian saldo tersimpan.');
    }

    public function balance(Request $request, LeaveRequestService $service): JsonResponse
    {
        $input = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'leave_category_id' => ['required', 'integer', 'exists:leave_categories,id'],
            'dates' => ['required', 'array', 'max:366'],
            'dates.*' => ['required', 'date_format:Y-m-d', 'after_or_equal:0001-01-01'],
        ]);
        $years = collect($input['dates'])->map(fn (string $date): int => (int) substr($date, 0, 4))->unique()->sort()->values();
        $balances = $service->balances((int) $input['employee_id'], (int) $input['leave_category_id'], $years->all());

        return response()->json(['data' => $years->map(function (int $year) use ($balances): array {
            $balance = $balances->get($year);

            return [
                'entitlement_year' => $year,
                'available_days' => $balance === null ? null : number_format($balance->available_cents / 100, 2, '.', ''),
            ];
        })]);
    }

    public function create(): View
    {
        return $this->form(false);
    }

    public function createEntitlement(): View
    {
        return $this->form(true);
    }

    public function store(StoreLeaveRequest $request, LeaveRequestService $service): RedirectResponse
    {
        $leave = $service->submit($request->validated());

        return to_route('admin.leave-requests.show', $leave)->with('leave_status', 'Permohonan cuti tercatat.');
    }

    public function storeEntitlement(StoreLeaveRequest $request, LeaveRequestService $service): RedirectResponse
    {
        $service->grant($request->validated());

        return to_route('admin.leave-requests.entitlements.create')->with('leave_status', 'Jatah cuti tersimpan.');
    }

    public function show(EmployeeRequest $leaveRequest): View
    {
        abort_unless($leaveRequest->request_type === 'leave', 404);
        $detail = DB::table('leave_request_details')->join('leave_categories', 'leave_categories.id', '=', 'leave_category_id')
            ->select('leave_request_details.*', 'leave_categories.name as category')->where('request_id', $leaveRequest->id)->first();
        abort_if($detail === null, 404);
        $days = DB::table('leave_request_days')->join('employee_leave_entitlements', 'employee_leave_entitlements.id', '=', 'employee_leave_entitlement_id')
            ->where('leave_request_detail_id', $leaveRequest->id)->orderBy('leave_date')
            ->get(['leave_date', 'duration_days', 'entitlement_year']);

        return view('admin.employees.leave-requests.show', compact('leaveRequest', 'detail', 'days'));
    }

    public function attachment(EmployeeRequest $leaveRequest): StreamedResponse
    {
        abort_unless($leaveRequest->request_type === 'leave', 404);
        $path = DB::table('leave_request_details')->where('request_id', $leaveRequest->id)->value('attachment_path');
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');
        abort_unless($path && $disk->exists($path), 404);

        return $disk->download($path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function form(bool $entitlement): View
    {
        return view('admin.employees.leave-requests.form', [
            'entitlement' => $entitlement,
            'categories' => LeaveCategory::where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
