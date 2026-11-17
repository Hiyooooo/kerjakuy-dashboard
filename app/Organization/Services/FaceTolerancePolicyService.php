<?php

namespace App\Organization\Services;

use App\Organization\Models\FaceTolerancePolicy;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FaceTolerancePolicyService
{
    public function save(FaceTolerancePolicy $policy, array $data, int $actorId, ?string $editVersion = null): void
    {
        try {
            DB::transaction(function () use ($policy, $data, $actorId, $editVersion): void {
                if ($policy->exists) {
                    $policy = $this->lockCurrent($policy, $editVersion);
                }

                $end = $data['effective_end_date'] ?? null;
                $overlap = $this->findOverlap($policy, $data['effective_start_date'], $end);

                if ($overlap) {
                    throw $this->overlapError($overlap);
                }

                if (! $policy->exists) {
                    $policy->created_by_user_id = $actorId;
                }

                $policy->fill([...$data, 'effective_end_date' => $end])->saveOrFail();
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23P01') {
                throw $this->overlapError($this->findOverlap($policy, $data['effective_start_date'], $data['effective_end_date'] ?? null));
            }

            throw $exception;
        }
    }

    public function replace(FaceTolerancePolicy $policy, array $data, int $actorId, string $editVersion): void
    {
        DB::transaction(function () use ($policy, $data, $actorId, $editVersion): void {
            $current = $this->lockCurrent($policy, $editVersion);
            $starts = CarbonImmutable::parse($data['effective_start_date']);
            $previousEnd = $starts->subDay()->toDateString();

            if ($starts->toDateString() <= today('Asia/Jakarta')->toDateString()
                || $previousEnd < $current->effective_start_date->toDateString()
                || ($current->effective_end_date !== null && $previousEnd > $current->effective_end_date->toDateString())) {
                throw ValidationException::withMessages([
                    'effective_start_date' => 'Pilih tanggal setelah hari ini dan tanggal mulai kebijakan lama, paling lambat sehari setelah kebijakan lama berakhir.',
                ]);
            }

            $current->update(['effective_end_date' => $previousEnd]);
            $this->save(new FaceTolerancePolicy, $data, $actorId);
        });
    }

    private function lockCurrent(FaceTolerancePolicy $policy, ?string $editVersion): FaceTolerancePolicy
    {
        $current = FaceTolerancePolicy::query()->lockForUpdate()->findOrFail($policy->getKey());

        if ($editVersion === null || ! hash_equals($current->editVersion(), $editVersion)) {
            throw ValidationException::withMessages([
                'edit_version' => 'Kebijakan sudah berubah. Buka versi terbaru sebelum menyimpan kembali.',
            ]);
        }

        return $current;
    }

    private function findOverlap(FaceTolerancePolicy $policy, string $start, ?string $end): ?FaceTolerancePolicy
    {
        return FaceTolerancePolicy::query()
            ->when($policy->exists, fn ($query) => $query->whereKeyNot($policy))
            ->when($end !== null, fn ($query) => $query->whereDate('effective_start_date', '<=', $end))
            ->where(fn ($query) => $query->whereNull('effective_end_date')
                ->orWhereDate('effective_end_date', '>=', $start))
            ->orderBy('effective_start_date')
            ->first();
    }

    private function overlapError(?FaceTolerancePolicy $overlap): ValidationException
    {
        $message = 'Periode bertumpang tindih dengan kebijakan lain.';

        if ($overlap !== null) {
            $start = $overlap->effective_start_date->locale('id')->translatedFormat('j F Y');
            $end = $overlap->effective_end_date?->locale('id')->translatedFormat('j F Y');
            $period = $end === null ? "mulai $start tanpa tanggal akhir" : "$start sampai $end";
            $message = "Periode bertumpang tindih dengan kebijakan yang berlaku $period.";
        }

        return ValidationException::withMessages([
            'effective_start_date' => $message.' Sesuaikan tanggal pada salah satu kebijakan.',
        ]);
    }
}
