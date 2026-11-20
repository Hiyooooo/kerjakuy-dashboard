@extends('layouts.admin')

@section('title', 'Hari libur')

@section('content')
    <header data-enter class="admin-page-header">
        <div>
            <h1 class="admin-title">Hari libur</h1>
            <p class="mt-2 max-w-2xl text-muted">Kelola tanggal libur untuk semua kantor atau satu kantor tertentu.</p>
        </div>
        <a href="{{ route('admin.holidays.create', $filters) }}" class="admin-button admin-button--primary">Tambah libur manual</a>
    </header>

    <section aria-labelledby="holiday-import-heading" class="mt-6 admin-panel p-5 sm:p-6">
        <h2 id="holiday-import-heading" class="font-heading text-xl font-extrabold">Libur resmi {{ $year }}</h2>
        <p class="mt-1 text-sm text-muted">Impor libur nasional dan cuti bersama untuk semua kantor. Entri resmi diperbarui lewat impor ulang.</p>
        <p class="mt-1 text-sm text-muted">Gunakan cuti bersama sebagai acuan sesuai kebijakan jadwal perusahaan.</p>

        <form method="POST" action="{{ route('admin.holidays.import', $filters) }}" class="mt-4 flex flex-wrap items-end gap-3" data-holiday-import>
            @csrf
            @php($importYear = old('year', $year >= 1000 ? $year : now('Asia/Jakarta')->year))
            <div>
                <label for="holiday-import-year" class="block text-sm font-bold">Tahun impor</label>
                <input id="holiday-import-year" name="year" type="number" min="1000" max="9999" step="1" required
                    value="{{ is_scalar($importYear) ? $importYear : '' }}" class="mt-1 min-h-12 w-28 rounded-[10px] border border-control px-3 tabular-nums"
                    @error('year') aria-invalid="true" aria-describedby="holiday-import-error" @enderror>
            </div>
            <button type="submit" class="min-h-12 cursor-pointer rounded-[10px] border border-control bg-white px-4 font-bold hover:bg-paper disabled:cursor-wait disabled:opacity-75 motion-safe:transition-colors">Impor libur resmi</button>
        </form>
        @error('year')
            <p id="holiday-import-error" role="alert" class="mt-2 text-sm text-[#b42318]">{{ $message }}</p>
        @enderror

        <p class="mt-3 text-sm text-muted">
            @if ($lastImport)
                Impor terakhir {{ $lastImport->locale('id')->translatedFormat('d F Y, H:i') }} WIB
            @else
                Belum ada impor berhasil untuk tahun {{ $year }}.
            @endif
        </p>
    </section>

    @if ($result = session('holiday_import_result'))
        <div class="mt-4 admin-panel px-5 py-3">
            <p role="status" class="font-bold text-[#26734d]">
                Impor {{ $result['year'] }} berhasil. {{ count($result['added']) }} ditambahkan, {{ count($result['updated']) }} diperbarui,
                {{ count($result['withdrawn']) }} ditarik dari daftar resmi, {{ $result['unchanged'] }} sudah sesuai.
            </p>
            @if ($result['added'] || $result['updated'] || $result['withdrawn'])
                <details>
                    <summary class="flex min-h-11 cursor-pointer items-center font-bold underline underline-offset-4">Lihat rincian impor</summary>
                    @foreach (['added' => 'Ditambahkan', 'updated' => 'Diperbarui', 'withdrawn' => 'Ditarik dari daftar resmi'] as $key => $label)
                        @if ($result[$key])
                            <p class="mt-2 font-bold">{{ $label }}</p>
                            <ul class="list-disc space-y-1 pb-2 pl-5 text-sm text-muted">
                                @foreach ($result[$key] as $change)
                                    <li>{{ $change }}</li>
                                @endforeach
                            </ul>
                        @endif
                    @endforeach
                </details>
            @endif
        </div>
    @endif

    @if (session('organization_status'))
        <p role="status" class="mt-4 rounded-[10px] border border-line bg-white px-4 py-3 text-[#26734d]">{{ session('organization_status') }}</p>
    @endif

    <section aria-labelledby="holiday-list-heading" class="mt-8">
        <h2 id="holiday-list-heading" class="font-heading text-xl font-extrabold">Daftar libur {{ $year }}</h2>

        <form method="GET" action="{{ route('admin.holidays.index') }}" aria-label="Filter daftar libur" class="mt-4 flex flex-wrap items-end gap-3">
            <div class="flex w-full min-w-0 items-end gap-3 lg:w-auto">
                <div>
                    <label for="holiday-filter-year" class="block text-sm font-bold">Tahun</label>
                    <input id="holiday-filter-year" name="filter_year" type="number" min="1" max="9999" step="1" required
                        value="{{ $year }}" class="mt-1 min-h-12 w-28 rounded-[10px] border border-control bg-white px-3 tabular-nums">
                </div>
                <div class="min-w-0 flex-1 lg:w-52">
                    <label for="holiday-filter-office" class="block text-sm font-bold">Kantor</label>
                    <select id="holiday-filter-office" name="office" class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3">
                        <option value="">Tampilkan semua kantor</option>
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" @selected(($filters['office'] ?? null) === $office->id)>
                                {{ $office->name }} ({{ $office->code }}){{ $office->is_active ? '' : ' · Nonaktif' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="admin-button admin-button--secondary">Terapkan filter</button>
        </form>
        @if (isset($filters['office']))
            <p class="mt-2 text-sm text-muted">Daftar mencakup libur yang berlaku untuk semua kantor dan libur khusus kantor pilihan.</p>
        @endif

        @if ($records->isEmpty())
            <p class="mt-4 admin-panel p-5 text-muted">
                Belum ada hari libur untuk pilihan ini. Impor libur resmi atau tambahkan libur manual.
            </p>
        @else
            <div class="mt-4 admin-panel admin-table" role="region" aria-label="Daftar hari libur" tabindex="0">
                <table class="w-full table-fixed text-left text-sm">
                    <thead class="border-b border-line bg-paper">
                        <tr>
                            <th scope="col" class="px-3 py-3 sm:px-5">Tanggal, libur, dan cakupan</th>
                            <th scope="col" class="w-16 px-2 py-3 text-right sm:w-24 sm:px-5">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($records as $record)
                            <tr class="focus-within:bg-paper/60 hover:bg-paper/60">
                                <th scope="row" class="px-3 py-3 font-bold wrap-anywhere sm:px-5">
                                    <time datetime="{{ $record->holiday_date->toDateString() }}" class="block text-xs font-semibold text-muted tabular-nums">{{ $record->holiday_date->locale('id')->translatedFormat('d F Y') }}</time>
                                    <span class="mt-0.5 block">{{ $record->name }}</span>
                                    <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1">
                                        <span @class([
                                            'rounded-[14px] px-2 py-1 text-xs font-bold',
                                            'bg-amber/20 text-[#874400]' => $record->official_type !== null,
                                            'bg-paper text-muted' => $record->official_type === null,
                                        ])>{{ match ($record->official_type) {
                                            'national_holiday' => 'Libur nasional',
                                            'joint_leave' => 'Cuti bersama',
                                            default => 'Manual',
                                        } }}</span>
                                        <span class="font-normal text-muted">{{ $record->office ? $record->office->name.' ('.$record->office->code.')' : 'Semua kantor' }}</span>
                                    </div>
                                    @if ($record->description)
                                        <span class="mt-1 block font-normal text-muted">{{ Str::limit($record->description, 120) }}</span>
                                    @endif
                                </th>
                                <td class="px-2 py-3 text-right sm:px-5">
                                    @if ($record->official_type === null)
                                        <a href="{{ route('admin.holidays.edit', [$record, ...$filters]) }}" aria-label="Edit {{ $record->name }}, {{ $record->holiday_date->locale('id')->translatedFormat('d F Y') }}, {{ $record->office?->name ?? 'Semua kantor' }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[10px] px-2 font-bold underline decoration-control/50 underline-offset-4 hover:bg-paper">Edit</a>
                                    @else
                                        <span aria-label="Dikelola lewat impor" class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($records->hasPages())
                <div class="mt-5">{{ $records->links() }}</div>
            @endif
        @endif
    </section>
@endsection
