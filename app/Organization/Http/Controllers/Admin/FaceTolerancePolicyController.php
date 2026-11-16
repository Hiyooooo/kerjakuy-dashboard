<?php

namespace App\Organization\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Organization\Http\Requests\SaveFaceTolerancePolicyRequest;
use App\Organization\Models\FaceTolerancePolicy;
use App\Organization\Services\FaceTolerancePolicyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class FaceTolerancePolicyController extends Controller
{
    public function __construct(private readonly FaceTolerancePolicyService $policies) {}

    public function index(): View
    {
        return view('admin.organization.face-tolerance-index', [
            'policies' => FaceTolerancePolicy::orderByDesc('effective_start_date')->paginate(15),
            'today' => today('Asia/Jakarta')->toDateString(),
        ]);
    }

    public function create(): View
    {
        return $this->edit(new FaceTolerancePolicy);
    }

    public function store(SaveFaceTolerancePolicyRequest $request): RedirectResponse
    {
        return $this->update($request, new FaceTolerancePolicy);
    }

    public function edit(FaceTolerancePolicy $faceTolerancePolicy): View
    {
        return view('admin.organization.face-tolerance-form', ['policy' => $faceTolerancePolicy]);
    }

    public function update(SaveFaceTolerancePolicyRequest $request, FaceTolerancePolicy $faceTolerancePolicy): RedirectResponse
    {
        $this->policies->save(
            $faceTolerancePolicy,
            $request->safe()->except('edit_version'),
            $request->user()->id,
            $request->validated('edit_version'),
        );

        return to_route('admin.face-tolerance-policies.index')->with('organization_status', 'Kebijakan toleransi wajah tersimpan.');
    }

    public function replacement(FaceTolerancePolicy $faceTolerancePolicy): View
    {
        return view('admin.organization.face-tolerance-form', [
            'policy' => new FaceTolerancePolicy(['minimum_match_score' => $faceTolerancePolicy->minimum_match_score]),
            'previousPolicy' => $faceTolerancePolicy,
        ]);
    }

    public function replace(SaveFaceTolerancePolicyRequest $request, FaceTolerancePolicy $faceTolerancePolicy): RedirectResponse
    {
        $this->policies->replace(
            $faceTolerancePolicy,
            $request->safe()->except('edit_version'),
            $request->user()->id,
            $request->validated('edit_version'),
        );

        return to_route('admin.face-tolerance-policies.index')->with('organization_status', 'Kebijakan pengganti berhasil dijadwalkan.');
    }
}
