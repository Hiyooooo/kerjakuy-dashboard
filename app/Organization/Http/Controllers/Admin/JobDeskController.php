<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\StoreJobDeskRequest;
use App\Organization\Http\Requests\UpdateJobDeskRequest;
use App\Organization\Models\JobDesk;
use App\Organization\Models\Position;
use App\Organization\Services\JobDeskService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class JobDeskController extends Controller
{
    public function __construct(private readonly JobDeskService $jobDesks) {}

    public function index(): View
    {
        return view('admin.organization.index', [
            'records' => $this->jobDesks->paginate(),
            'resource' => 'job-desks',
            'label' => 'Job desk',
        ]);
    }

    public function create(): View
    {
        return $this->form(new JobDesk);
    }

    public function store(StoreJobDeskRequest $request): RedirectResponse
    {
        $this->jobDesks->create($request->validated());

        return to_route('admin.job-desks.index')->with('organization_status', 'Job desk berhasil ditambahkan.');
    }

    public function edit(JobDesk $jobDesk): View
    {
        return $this->form($jobDesk);
    }

    public function update(UpdateJobDeskRequest $request, JobDesk $jobDesk): RedirectResponse
    {
        $this->jobDesks->update($request->validated(), $jobDesk);

        return to_route('admin.job-desks.index')->with('organization_status', 'Job desk berhasil diperbarui.');
    }

    public function destroy(JobDesk $jobDesk): RedirectResponse
    {
        $this->jobDesks->delete($jobDesk);

        return to_route('admin.job-desks.index')->with('organization_status', 'Job desk berhasil dihapus.');
    }

    private function form(JobDesk $record): View
    {
        return view('admin.organization.form', [
            'record' => $record,
            'resource' => 'job-desks',
            'label' => 'Job desk',
            'positions' => Position::orderBy('name')->orderBy('id')->get(['id', 'code', 'name', 'is_active']),
        ]);
    }
}
