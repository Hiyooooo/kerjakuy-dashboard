<?php

namespace App\Scheduling\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Models\Office;
use App\Scheduling\Http\Requests\SaveWorkScheduleTemplateRequest;
use App\Scheduling\Models\WorkScheduleTemplate;
use App\Scheduling\Models\WorkShift;
use App\Scheduling\Services\WorkScheduleTemplateService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class WorkScheduleTemplateController extends Controller
{
    public function __construct(private readonly WorkScheduleTemplateService $master) {}

    public function index(): View
    {
        return view('admin.scheduling.index', [
            'records' => WorkScheduleTemplate::with(['office', 'days.shift'])->orderBy('name')->orderBy('id')->paginate(15),
            'resource' => 'work-schedule-templates',
            'label' => 'Template jadwal mingguan',
        ]);
    }

    public function create(): View
    {
        return $this->edit(new WorkScheduleTemplate);
    }

    public function store(SaveWorkScheduleTemplateRequest $request): RedirectResponse
    {
        return $this->update($request, new WorkScheduleTemplate);
    }

    public function edit(WorkScheduleTemplate $workScheduleTemplate): View
    {
        return view('admin.scheduling.form', [
            'record' => $workScheduleTemplate,
            'resource' => 'work-schedule-templates',
            'label' => 'Template jadwal mingguan',
            'options' => Office::orderBy('name')->get(),
            'shifts' => WorkShift::with('attendanceTime')->orderBy('name')->get(),
        ]);
    }

    public function update(SaveWorkScheduleTemplateRequest $request, WorkScheduleTemplate $workScheduleTemplate): RedirectResponse
    {
        try {
            $this->master->save($workScheduleTemplate, $request->safe()->except(['days', 'edit_version']), $request->validated('days'), $request->validated('edit_version'));
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.work-schedule-templates.index')->with('scheduling_status', 'Template jadwal mingguan tersimpan.');
    }
}
