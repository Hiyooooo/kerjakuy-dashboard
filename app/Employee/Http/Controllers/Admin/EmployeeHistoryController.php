<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\EmployeeHistory;
use App\Employee\Http\Requests\SaveEmployeeHistoryRequest;
use App\Employee\Models\Employee;
use App\Employee\Services\EmployeeHistoryService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeHistoryController extends Controller
{
    public function __construct(private readonly EmployeeHistoryService $historyService) {}

    public function index(Request $request, Employee $employee): View
    {
        $history = EmployeeHistory::from($request->route('history'));
        $records = $history->records($employee);
        if ($history === EmployeeHistory::Coaching) {
            $records->with('createdBy:id,name')->orderByDesc('coaching_date');
        }

        return view('admin.employees.history-index', [
            'employee' => $employee,
            'history' => $history,
            'records' => $records->latest('id')->paginate(15),
        ]);
    }

    public function create(Request $request, Employee $employee): View
    {
        return $this->form($employee, EmployeeHistory::from($request->route('history')), null);
    }

    public function edit(Request $request, Employee $employee, string $record): View
    {
        $history = EmployeeHistory::from($request->route('history'));

        return $this->form($employee, $history, $history->records($employee)->findOrFail($record));
    }

    public function store(SaveEmployeeHistoryRequest $request, Employee $employee): RedirectResponse
    {
        return $this->save($request, $employee, null);
    }

    public function update(SaveEmployeeHistoryRequest $request, Employee $employee, string $record): RedirectResponse
    {
        return $this->save($request, $employee, $record);
    }

    public function certificate(Employee $employee, string $record): StreamedResponse
    {
        $training = $employee->trainings()->findOrFail($record);
        $disk = Storage::disk('local');
        $path = $training->certificate_file_path;
        abort_unless($path && $disk->exists($path), 404);

        return $disk->download($path, 'sertifikat-pelatihan-'.$training->getKey().'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function form(Employee $employee, EmployeeHistory $history, ?Model $record): View
    {
        return view('admin.employees.history-form', [
            'employee' => $employee,
            'history' => $history,
            'record' => $record,
            'version' => $record === null ? null : $this->historyService->version($record),
        ]);
    }

    private function save(SaveEmployeeHistoryRequest $request, Employee $employee, ?string $record): RedirectResponse
    {
        $history = $request->history();
        try {
            $this->historyService->save($history, $employee, $request->user(), $request->validated(), $record);
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route($history->routeName('index'), $employee)->with('employee_status', 'Data '.mb_strtolower($history->label()).' tersimpan.');
    }
}
