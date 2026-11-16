<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\StoreLeaveCategoryRequest;
use App\Organization\Http\Requests\UpdateLeaveCategoryRequest;
use App\Organization\Models\LeaveCategory;
use App\Organization\Services\LeaveCategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LeaveCategoryController extends Controller
{
    public function __construct(private readonly LeaveCategoryService $leaveCategorys) {}

    public function index(): View
    {
        return view('admin.organization.index', [
            'records' => $this->leaveCategorys->paginate(),
            'resource' => 'leave-categories',
            'label' => 'Kategori cuti',
            'noteField' => 'description',
        ]);
    }

    public function create(): View
    {
        return $this->form(new LeaveCategory);
    }

    public function store(StoreLeaveCategoryRequest $request): RedirectResponse
    {
        $this->leaveCategorys->create($request->validated());

        return to_route('admin.leave-categories.index')->with('organization_status', 'Kategori cuti berhasil ditambahkan.');
    }

    public function edit(LeaveCategory $leaveCategory): View
    {
        return $this->form($leaveCategory);
    }

    public function update(UpdateLeaveCategoryRequest $request, LeaveCategory $leaveCategory): RedirectResponse
    {
        $this->leaveCategorys->update($request->validated(), $leaveCategory);

        return to_route('admin.leave-categories.index')->with('organization_status', 'Kategori cuti berhasil diperbarui.');
    }

    public function destroy(LeaveCategory $leaveCategory): RedirectResponse
    {
        $this->leaveCategorys->delete($leaveCategory);

        return to_route('admin.leave-categories.index')->with('organization_status', 'Kategori cuti berhasil dihapus.');
    }

    private function form(LeaveCategory $record): View
    {
        return view('admin.organization.form', [
            'record' => $record,
            'resource' => 'leave-categories',
            'label' => 'Kategori cuti',
            'noteField' => 'description',
            'noteLabel' => 'Deskripsi',
            'nameLimit' => 100,
        ]);
    }
}
