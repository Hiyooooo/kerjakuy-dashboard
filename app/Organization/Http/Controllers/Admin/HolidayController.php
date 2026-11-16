<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\ImportHolidayRequest;
use App\Organization\Http\Requests\StoreHolidayRequest;
use App\Organization\Http\Requests\UpdateHolidayRequest;
use App\Organization\Models\Holiday;
use App\Organization\Models\Office;
use App\Organization\Services\HolidayService;
use App\Organization\Services\IndonesianHolidayImporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HolidayController extends Controller
{
    public function __construct(private readonly HolidayService $holidays) {}

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $year = $filters['filter_year'] ?? now('Asia/Jakarta')->year;
        $lastImport = Holiday::whereYear('holiday_date', sprintf('%04d', $year))
            ->whereNotNull('official_type')->max('updated_at');

        return view('admin.organization.holidays', [
            'records' => $this->holidays->paginate($year, $filters['office'] ?? null)->appends($filters),
            'filters' => $filters,
            'year' => $year,
            'offices' => Office::orderBy('name')->orderBy('id')->get(['id', 'code', 'name', 'is_active']),
            'lastImport' => $lastImport === null ? null : Carbon::parse($lastImport)->setTimezone('Asia/Jakarta'),
        ]);
    }

    public function create(Request $request): View
    {
        return $this->form(new Holiday, $request);
    }

    public function import(ImportHolidayRequest $request, IndonesianHolidayImporter $importer): RedirectResponse
    {
        $year = $request->integer('year');
        $result = $importer->import($year);

        return to_route('admin.holidays.index', ['filter_year' => $year] + $this->filters($request))
            ->with('holiday_import_result', ['year' => $year] + $result);
    }

    public function store(StoreHolidayRequest $request): RedirectResponse
    {
        $this->holidays->create($request->holidayData());

        return to_route('admin.holidays.index', $this->filters($request))->with('organization_status', 'Hari libur berhasil ditambahkan.');
    }

    public function edit(Request $request, Holiday $holiday): View
    {
        abort_if($holiday->official_type !== null, 403);

        return $this->form($holiday, $request);
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): RedirectResponse
    {
        $this->holidays->update($request->holidayData(), $holiday);

        return to_route('admin.holidays.index', $this->filters($request))->with('organization_status', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Request $request, Holiday $holiday): RedirectResponse
    {
        $this->holidays->delete($holiday);

        return to_route('admin.holidays.index', $this->filters($request))->with('organization_status', 'Hari libur berhasil dihapus.');
    }

    private function form(Holiday $record, Request $request): View
    {
        return view('admin.organization.form', [
            'record' => $record,
            'resource' => 'holidays',
            'label' => 'Hari libur',
            'noteField' => 'description',
            'noteLabel' => 'Deskripsi',
            'nameLimit' => 150,
            'holidayFilters' => $this->filters($request),
            'offices' => Office::where(fn ($query) => $query->where('is_active', true)->orWhere('id', $record->office_id))
                ->orderBy('name')->orderBy('id')->get(['id', 'code', 'name', 'is_active']),
        ]);
    }

    /** @return array{filter_year?: int, office?: int} */
    private function filters(Request $request): array
    {
        $year = filter_var($request->query('filter_year'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 9999]]);
        $office = filter_var($request->query('office'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return array_filter([
            'filter_year' => $year,
            'office' => $office !== false && Office::whereKey($office)->exists() ? $office : false,
        ]);
    }
}
