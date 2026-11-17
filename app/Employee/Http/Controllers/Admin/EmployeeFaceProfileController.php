<?php

namespace App\Employee\Http\Controllers\Admin;

use App\Employee\Http\Requests\StoreFaceProfileRequest;
use App\Employee\Models\Employee;
use App\Employee\Models\EmployeeFaceProfile;
use App\Employee\Services\EmployeeFaceProfileService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeFaceProfileController extends Controller
{
    public function index(Employee $employee): Response
    {
        return response()->view('admin.employees.face-profiles', [
            'employee' => $employee,
            'profiles' => $employee->faceProfiles()->orderByDesc('id')->paginate(15),
        ], 200, ['Cache-Control' => 'private, no-store']);
    }

    public function store(StoreFaceProfileRequest $request, Employee $employee, EmployeeFaceProfileService $profiles): RedirectResponse
    {
        try {
            $profiles->store($employee, $request->validated('photo'), $request->user()->id);
        } catch (ValidationException $exception) {
            throw $request->forForm($exception);
        }

        return to_route('admin.employees.face-profiles.index', $employee)->with('employee_status', 'Foto referensi tersimpan.');
    }

    public function photo(Employee $employee, EmployeeFaceProfile $faceProfile): StreamedResponse
    {
        $disk = Storage::disk('local');
        $path = $faceProfile->reference_photo_path;

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
