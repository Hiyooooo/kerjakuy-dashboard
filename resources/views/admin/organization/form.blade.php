@extends('layouts.admin')

@php
    $editing = $record->exists;
    $title = ($editing ? 'Edit ' : 'Tambah ').Str::lower($label);
    $routeFilters = $holidayFilters ?? [];
    if ($resource === 'holidays') {
        $title = ($editing ? 'Edit' : 'Tambah').' libur manual';
    }
    $requiresNote = match (old('requires_note')) {
        '1', 1, true => true,
        '0', 0, false => false,
        default => $record->requires_note ?? true,
    };
    $isActive = match (old('is_active')) {
        '1', 1, true => true,
        '0', 0, false => false,
        default => $record->is_active ?? true,
    };
@endphp

@section('title', $title)

@section('breadcrumb-parent')
    <a href="{{ route('admin.'.$resource.'.index', $routeFilters) }}" class="inline-flex min-h-11 items-center hover:text-ink hover:underline">{{ $label }}</a>
@endsection

@section('content')
    <div class="mx-auto max-w-xl">
    <h1 data-enter class="font-heading text-3xl leading-tight font-extrabold tracking-tight sm:text-[40px]">{{ $title }}</h1>

    @if ($resource === 'job-desks' && $positions->isEmpty())
        <div class="mt-6 max-w-xl rounded-[22px] border border-line bg-white p-6">
            <p class="text-muted">Tambahkan jabatan untuk membuat job desk.</p>
            <a href="{{ route('admin.positions.create') }}" class="mt-3 inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4">Tambah jabatan</a>
        </div>
    @else
    <form method="POST" action="{{ $editing ? route('admin.'.$resource.'.update', [$record, ...$routeFilters]) : route('admin.'.$resource.'.store', $routeFilters) }}" class="mt-6 grid max-w-xl gap-5 rounded-[22px] border border-line bg-white p-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        @if ($resource === 'job-desks')
            @include('admin.organization.job-desk-fields')
        @elseif ($resource === 'attendance-times')
            @include('admin.organization.attendance-time-fields')
        @else
        @php
            $textFields = ['name' => ['Nama '.Str::lower($label), $nameLimit, match ($resource) {
                'positions' => 'Contoh: Lead Programmer.',
                'leave-categories' => 'Contoh: Cuti tahunan.',
                'out-of-location-reasons' => 'Contoh: Kunjungan klien.',
                default => null,
            }]];
            if ($resource === 'positions') {
                $textFields['code'] = ['Kode jabatan', 30, 'Contoh: LD-PGM.'];
            } elseif ($resource === 'offices') {
                $textFields['code'] = ['Kode kantor', 30, null];
            }
        @endphp
        @foreach ($textFields as $field => [$fieldLabel, $limit, $hint])
            @php($value = old($field, $record->{$field}))
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $fieldLabel }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ is_string($value) ? $value : '' }}" maxlength="{{ $limit }}" required
                    @if ($hint || $errors->has($field))
                        aria-describedby="{{ $hint ? $field.'-hint' : '' }} @error($field) {{ $field }}-error @enderror"
                    @endif
                    @error($field) aria-invalid="true" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @if ($hint)
                    <p id="{{ $field }}-hint" class="mt-1 text-sm text-muted">{{ $hint }}</p>
                @endif
                @error($field)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        @if ($resource === 'holidays')
            @include('admin.organization.holiday-fields')
        @endif

        @if ($resource === 'attendance-methods')
            @include('admin.organization.attendance-method-fields')
        @elseif ($resource === 'attendance-locations')
            @include('admin.organization.attendance-location-fields')
        @elseif ($resource === 'out-of-location-reasons')
            <div>
                <label for="requires_note" class="block text-sm font-bold">Catatan pegawai</label>
                <p id="requires-note-hint" class="mt-1 text-sm text-muted">Tentukan apakah pegawai wajib menulis catatan saat memilih alasan ini.</p>
                <select id="requires_note" name="requires_note" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                    aria-describedby="requires-note-hint @error('requires_note') requires-note-error @enderror" @error('requires_note') aria-invalid="true" @enderror>
                    <option value="1" @selected($requiresNote)>Wajib</option>
                    <option value="0" @selected(! $requiresNote)>Opsional</option>
                </select>
                @error('requires_note')
                    <p id="requires-note-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @else
        <div>
            <label for="{{ $noteField }}" class="block text-sm font-bold">{{ $noteLabel }} (opsional)</label>
            <textarea id="{{ $noteField }}" name="{{ $noteField }}" rows="3" maxlength="2000"
                @error($noteField) aria-invalid="true" aria-describedby="{{ $noteField }}-error" @enderror
                class="mt-1 w-full rounded-[10px] border border-control px-3 py-2">{{ old($noteField, $record->{$noteField}) }}</textarea>
            @error($noteField)
                <p id="{{ $noteField }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>
        @endif

        @if ($resource === 'offices')
            <fieldset class="grid gap-3">
                <legend class="mb-2 text-sm font-bold">Koordinat kantor (opsional)</legend>
                <p id="coordinates-hint" class="text-sm text-muted">Isi lintang dan bujur sekaligus, atau kosongkan keduanya. Contoh: lintang -6.2 dan bujur 106.8.</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['latitude' => ['Lintang', 90], 'longitude' => ['Bujur', 180]] as $field => [$fieldLabel, $limit])
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-bold">{{ $fieldLabel }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="number" step="0.0000001" min="{{ -$limit }}" max="{{ $limit }}"
                                value="{{ old($field, $record->{$field}) }}" aria-describedby="coordinates-hint @error($field) {{ $field }}-error @enderror"
                                @error($field) aria-invalid="true" @enderror class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                            @error($field)
                                <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>
        @endif
        @endif

        @if ($resource !== 'holidays')
        <div>
            <label for="is_active" class="block text-sm font-bold">Status</label>
            <select id="is_active" name="is_active" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                @error('is_active') aria-invalid="true" aria-describedby="is_active-error" @enderror>
                <option value="1" @selected($isActive)>Aktif</option>
                <option value="0" @selected(! $isActive)>Nonaktif</option>
            </select>
            @error('is_active')
                <p id="is_active-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>
        @endif

        <div class="flex flex-wrap gap-2 border-t border-line pt-4">
            <button type="submit" class="min-h-12 cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">Simpan {{ Str::lower($label) }}</button>
            <a href="{{ route('admin.'.$resource.'.index', $routeFilters) }}" class="inline-flex min-h-12 items-center rounded-[10px] px-4 font-bold hover:bg-paper">Batal</a>
        </div>
    </form>
    @endif

    @if ($editing && ! in_array($resource, ['attendance-methods', 'attendance-locations'], true))
        <details class="mt-4 max-w-xl rounded-[22px] border border-line bg-white px-6 py-2" @if ($errors->has('delete')) open @endif>
            <summary class="min-h-11 cursor-pointer py-3 font-bold text-[#b42318]">Hapus {{ Str::lower($label) }}</summary>
            <form method="POST" action="{{ route('admin.'.$resource.'.destroy', [$record, ...$routeFilters]) }}" class="space-y-3 pb-4">
                @csrf
                @method('DELETE')
                <p>Hapus {{ $resource === 'job-desks' ? $record->title : $record->name }} secara permanen?</p>
                @if ($resource === 'holidays')
                    <p class="text-sm text-muted">{{ $record->holiday_date->locale('id')->translatedFormat('d F Y') }} · {{ $record->office?->name ?? 'Semua kantor' }}</p>
                @endif
                @error('delete')
                    <p role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
                <button type="submit" class="min-h-11 cursor-pointer rounded-[10px] border border-[#b42318] px-4 font-bold text-[#b42318] hover:bg-paper">Ya, hapus {{ Str::lower($label) }}</button>
            </form>
        </details>
    @endif
    </div>
@endsection
