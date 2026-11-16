<?php

namespace App\Organization\Services;

use App\Organization\Models\Holiday;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class IndonesianHolidayImporter
{
    /** @return array{added: list<string>, updated: list<string>, withdrawn: list<string>, unchanged: int} */
    public function import(int $year): array
    {
        $lock = Cache::lock("holiday-import:{$year}", 30);

        if (! $lock->get()) {
            throw ValidationException::withMessages(['year' => "Impor tahun {$year} sedang berjalan. Tunggu sebentar lalu coba lagi."]);
        }

        try {
            return $this->sync($year);
        } finally {
            $lock->release();
        }
    }

    /** @return array{added: list<string>, updated: list<string>, withdrawn: list<string>, unchanged: int} */
    private function sync(int $year): array
    {
        try {
            $response = Http::acceptJson()
                ->withoutRedirecting()
                ->connectTimeout(3)
                ->timeout(8)
                ->get("https://indonesian-holidays.ahrulsyamil.com/holidays/{$year}");
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['year' => 'Layanan libur resmi tidak dapat dihubungi. Coba lagi nanti.']);
        }

        if ($response->notFound()) {
            throw ValidationException::withMessages(['year' => "Data libur resmi tahun {$year} belum tersedia."]);
        }

        if (! $response->successful()) {
            throw ValidationException::withMessages(['year' => 'Layanan libur resmi sedang bermasalah. Coba lagi nanti.']);
        }

        $payload = $response->json();
        $validator = Validator::make(['data' => data_get($payload, 'data')], [
            'data' => ['present', 'array', 'list'],
            'data.*' => ['required', 'array'],
            'data.*.date' => ['required', 'date_format:Y-m-d', "starts_with:{$year}-"],
            'data.*.name' => ['required', 'string', 'max:150', 'not_regex:/\\x00/'],
            'data.*.type' => ['required', 'in:national_holiday,joint_leave'],
            'data.*.description' => ['nullable', 'string', 'max:2000', 'not_regex:/\\x00/'],
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages(['year' => 'Respons API hari libur tidak valid. Impor dibatalkan.']);
        }

        $data = $validator->validated()['data'];

        if (data_get($payload, 'meta.year') !== $year || data_get($payload, 'meta.count') !== count($data)) {
            throw ValidationException::withMessages(['year' => 'Daftar libur resmi tidak lengkap. Impor dibatalkan.']);
        }

        if ($data === []) {
            throw ValidationException::withMessages(['year' => "Data libur resmi tahun {$year} belum tersedia."]);
        }

        $incoming = collect($data)->sortBy(fn (array $row): int => $row['type'] === 'national_holiday' ? 0 : 1)
            ->unique(fn (array $row): string => $row['date'].' '.$row['name'])
            ->keyBy(fn (array $row): string => $row['date'].' '.$row['name'])
            ->sortKeys();

        return DB::transaction(function () use ($year, $incoming): array {
            $result = ['added' => [], 'updated' => [], 'withdrawn' => [], 'unchanged' => 0];
            $now = now();
            $oldOfficial = Holiday::whereYear('holiday_date', $year)->whereNotNull('official_type')
                ->lockForUpdate()->get()->keyBy(fn (Holiday $holiday): string => $holiday->holiday_date->toDateString().' '.$holiday->name);

            foreach ($incoming as $key => $row) {
                $date = (new Holiday(['holiday_date' => $row['date']]))->getAttributes()['holiday_date'];
                $inserted = DB::affectingStatement('
                    INSERT INTO holidays (office_id, holiday_date, name, description, official_type, created_at, updated_at)
                    VALUES (NULL, ?, ?, ?, ?, ?, ?)
                    ON CONFLICT (holiday_date, name) WHERE office_id IS NULL DO NOTHING
                ', [$date, $row['name'], $row['description'] ?? null, $row['type'], $now, $now]);

                if ($inserted === 1) {
                    $result['added'][] = $key;

                    continue;
                }

                $holiday = Holiday::whereNull('office_id')->where('holiday_date', $date)->where('name', $row['name'])
                    ->lockForUpdate()->firstOrFail();
                $manualOrigin = $holiday->official_type === null || $holiday->has_manual_origin;
                $holiday->forceFill([
                    'official_type' => $row['type'],
                    'has_manual_origin' => $manualOrigin,
                    'description' => $manualOrigin ? $holiday->description : ($row['description'] ?? null),
                ]);

                if ($holiday->isDirty()) {
                    $result['updated'][] = $key;
                } else {
                    $result['unchanged']++;
                }

                $holiday->updated_at = $now;
                $holiday->save();
            }

            foreach ($oldOfficial->diffKeys($incoming)->sortKeys() as $key => $holiday) {
                $result['withdrawn'][] = $key;

                if ($holiday->has_manual_origin) {
                    $holiday->forceFill(['official_type' => null, 'has_manual_origin' => false])->save();
                } else {
                    $holiday->delete();
                }
            }

            return $result;
        });
    }
}
