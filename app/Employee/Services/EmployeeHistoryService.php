<?php

namespace App\Employee\Services;

use App\Access\Models\User;
use App\Employee\EmployeeHistory;
use App\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmployeeHistoryService
{
    public function version(Model $record): string
    {
        return hash('sha256', serialize($record->getRawOriginal()));
    }

    /** @param array<string, mixed> $data */
    public function save(EmployeeHistory $history, Employee $employee, User $actor, array $data, ?string $recordId): void
    {
        $newCertificate = null;

        try {
            $replacedCertificate = DB::transaction(function () use ($history, $employee, $actor, $data, $recordId, &$newCertificate): ?string {
                $records = $history->records($employee);
                $record = $recordId === null ? $records->make() : $records->lockForUpdate()->findOrFail($recordId);
                if ($record->exists && ! hash_equals($this->version($record), $data['edit_version'])) {
                    throw ValidationException::withMessages(['edit_version' => 'Data sudah berubah. Buka versi terbaru sebelum menyimpan.']);
                }

                foreach ($history->fields() as $field => $definition) {
                    $record->setAttribute($field, $data[$field] ?? null);
                }

                if ($history === EmployeeHistory::Coaching && ! $record->exists) {
                    $record->setAttribute('created_by_user_id', $actor->getKey());
                }

                $previousPath = null;
                if ($history === EmployeeHistory::Training && isset($data['certificate'])) {
                    $newCertificate = $data['certificate']->store('employee-training-certificates', 'local');
                    if ($newCertificate === false) {
                        throw ValidationException::withMessages(['certificate' => 'File sertifikat gagal disimpan. Coba lagi.']);
                    }

                    $previousPath = $record->getAttribute('certificate_file_path');
                    $record->setAttribute('certificate_file_path', $newCertificate);
                } elseif ($history === EmployeeHistory::Training && ($data['remove_certificate'] ?? false)) {
                    $previousPath = $record->getAttribute('certificate_file_path');
                    $record->setAttribute('certificate_file_path', null);
                }

                $record->saveOrFail();

                return $previousPath;
            });
        } catch (Throwable $exception) {
            if (is_string($newCertificate)) {
                Storage::disk('local')->delete($newCertificate);
            }

            throw $exception;
        }

        if ($replacedCertificate !== null) {
            Storage::disk('local')->delete($replacedCertificate);
        }
    }
}
