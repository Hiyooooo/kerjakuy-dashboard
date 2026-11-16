<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\StoreOutOfLocationReasonRequest;
use App\Organization\Http\Requests\UpdateOutOfLocationReasonRequest;
use App\Organization\Models\OutOfLocationReason;
use App\Organization\Services\OutOfLocationReasonService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OutOfLocationReasonController extends Controller
{
    public function __construct(private readonly OutOfLocationReasonService $reasons) {}

    public function index(): View
    {
        return view('admin.organization.index', [
            'records' => $this->reasons->paginate(),
            'resource' => 'out-of-location-reasons',
            'label' => 'Alasan di luar lokasi',
        ]);
    }

    public function create(): View
    {
        return $this->form(new OutOfLocationReason);
    }

    public function store(StoreOutOfLocationReasonRequest $request): RedirectResponse
    {
        $this->reasons->create($request->validated());

        return to_route('admin.out-of-location-reasons.index')->with('organization_status', 'Alasan di luar lokasi berhasil ditambahkan.');
    }

    public function edit(OutOfLocationReason $outOfLocationReason): View
    {
        return $this->form($outOfLocationReason);
    }

    public function update(UpdateOutOfLocationReasonRequest $request, OutOfLocationReason $outOfLocationReason): RedirectResponse
    {
        $this->reasons->update($request->validated(), $outOfLocationReason);

        return to_route('admin.out-of-location-reasons.index')->with('organization_status', 'Alasan di luar lokasi berhasil diperbarui.');
    }

    public function destroy(OutOfLocationReason $outOfLocationReason): RedirectResponse
    {
        $this->reasons->delete($outOfLocationReason);

        return to_route('admin.out-of-location-reasons.index')->with('organization_status', 'Alasan di luar lokasi berhasil dihapus.');
    }

    private function form(OutOfLocationReason $record): View
    {
        return view('admin.organization.form', [
            'record' => $record,
            'resource' => 'out-of-location-reasons',
            'label' => 'Alasan di luar lokasi',
            'nameLimit' => 120,
        ]);
    }
}
