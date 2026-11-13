<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Models\Office;
use App\Services\OfficeService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OfficeController extends Controller
{
    public function __construct(private readonly OfficeService $offices) {}

    public function index(): View
    {
        return view('admin.organization.index', [
            'records' => $this->offices->paginate(),
            'resource' => 'offices',
            'label' => 'Kantor',
            'noteField' => 'address',
        ]);
    }

    public function create(): View
    {
        return $this->form(new Office);
    }

    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        $this->offices->create($request->validated());

        return to_route('admin.offices.index')->with('organization_status', 'Kantor berhasil ditambahkan.');
    }

    public function edit(Office $office): View
    {
        return $this->form($office);
    }

    public function update(UpdateOfficeRequest $request, Office $office): RedirectResponse
    {
        $this->offices->update($request->validated(), $office);

        return to_route('admin.offices.index')->with('organization_status', 'Kantor berhasil diperbarui.');
    }

    public function destroy(Office $office): RedirectResponse
    {
        $this->offices->delete($office);

        return to_route('admin.offices.index')->with('organization_status', 'Kantor berhasil dihapus.');
    }

    private function form(Office $record): View
    {
        return view('admin.organization.form', [
            'record' => $record,
            'resource' => 'offices',
            'label' => 'Kantor',
            'noteField' => 'address',
            'noteLabel' => 'Alamat',
            'nameLimit' => 120,
        ]);
    }
}
