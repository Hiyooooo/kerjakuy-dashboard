<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\StorePositionRequest;
use App\Organization\Http\Requests\UpdatePositionRequest;
use App\Organization\Models\Position;
use App\Organization\Services\PositionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PositionController extends Controller
{
    public function __construct(private readonly PositionService $positions) {}

    public function index(): View
    {
        return view('admin.organization.index', [
            'records' => $this->positions->paginate(),
            'resource' => 'positions',
            'label' => 'Jabatan',
            'noteField' => 'description',
        ]);
    }

    public function create(): View
    {
        return $this->form(new Position);
    }

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $this->positions->create($request->validated());

        return to_route('admin.positions.index')->with('organization_status', 'Jabatan berhasil ditambahkan.');
    }

    public function edit(Position $position): View
    {
        return $this->form($position);
    }

    public function update(UpdatePositionRequest $request, Position $position): RedirectResponse
    {
        $this->positions->update($request->validated(), $position);

        return to_route('admin.positions.index')->with('organization_status', 'Jabatan berhasil diperbarui.');
    }

    public function destroy(Position $position): RedirectResponse
    {
        $this->positions->delete($position);

        return to_route('admin.positions.index')->with('organization_status', 'Jabatan berhasil dihapus.');
    }

    private function form(Position $record): View
    {
        return view('admin.organization.form', [
            'record' => $record,
            'resource' => 'positions',
            'label' => 'Jabatan',
            'noteField' => 'description',
            'noteLabel' => 'Deskripsi',
            'nameLimit' => 100,
        ]);
    }
}
