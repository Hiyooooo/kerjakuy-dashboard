@extends('layouts.admin')

@php
    $editing = $record->exists;
    $title = ($editing ? 'Edit ' : 'Tambah ').Str::lower($label);
@endphp

@section('title', $title)

@section('content')
    <a href="{{ route('admin.'.$resource.'.index') }}" class="inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4">Kembali ke {{ Str::lower($label) }}</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $title }}</h1>

    @if ($resource === 'job-desks' && $positions->isEmpty())
        <div class="mt-6 max-w-xl rounded-[22px] border border-line bg-white p-6">
            <p class="text-muted">Tambahkan jabatan untuk membuat job desk.</p>
            <a href="{{ route('admin.positions.create') }}" class="mt-3 inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4">Tambah jabatan</a>
        </div>
    @else
    <form method="POST" action="{{ $editing ? route('admin.'.$resource.'.update', $record) : route('admin.'.$resource.'.store') }}" class="mt-6 grid max-w-xl gap-5 rounded-[22px] border border-line bg-white p-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        @if ($resource === 'job-desks')
            @include('admin.organization.job-desk-fields')
        @elseif ($resource === 'attendance-times')
            @include('admin.organization.attendance-time-fields')
        @else
        @foreach (['code' => ['Kode', 30], 'name' => ['Nama', $nameLimit]] as $field => [$fieldLabel, $limit])
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $fieldLabel }} {{ Str::lower($label) }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $record->{$field}) }}" maxlength="{{ $limit }}" required
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error($field)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div>
            <label for="{{ $noteField }}" class="block text-sm font-bold">{{ $noteLabel }} (opsional)</label>
            <textarea id="{{ $noteField }}" name="{{ $noteField }}" rows="3" maxlength="2000"
                @error($noteField) aria-invalid="true" aria-describedby="{{ $noteField }}-error" @enderror
                class="mt-1 w-full rounded-[10px] border border-control px-3 py-2">{{ old($noteField, $record->{$noteField}) }}</textarea>
            @error($noteField)
                <p id="{{ $noteField }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        @if ($resource === 'offices')
            <fieldset class="grid gap-3">
                <legend class="mb-2 text-sm font-bold">Koordinat kantor (opsional)</legend>
                <p id="coordinates-hint" class="text-sm text-muted">Isi lintang dan bujur jika koordinat diketahui.</p>
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

        <div>
            <label for="is_active" class="block text-sm font-bold">Status</label>
            <select id="is_active" name="is_active" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                @error('is_active') aria-invalid="true" aria-describedby="is_active-error" @enderror>
                <option value="1" @selected((string) old('is_active', (int) ($record->is_active ?? true)) === '1')>Aktif</option>
                <option value="0" @selected((string) old('is_active', (int) ($record->is_active ?? true)) === '0')>Nonaktif</option>
            </select>
            @error('is_active')
                <p id="is_active-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="min-h-12 w-fit cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">Simpan {{ Str::lower($label) }}</button>
    </form>
    @endif

    @if ($editing)
        <details class="mt-4 max-w-xl rounded-[22px] border border-line bg-white px-6 py-2" @if ($errors->has('delete')) open @endif>
            <summary class="min-h-11 cursor-pointer py-3 font-bold text-[#b42318]">Hapus {{ Str::lower($label) }}</summary>
            <form method="POST" action="{{ route('admin.'.$resource.'.destroy', $record) }}" class="space-y-3 pb-4">
                @csrf
                @method('DELETE')
                <p>Hapus {{ $resource === 'job-desks' ? $record->title : $record->name }} secara permanen?</p>
                @error('delete')
                    <p role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
                <button type="submit" class="min-h-11 cursor-pointer rounded-[10px] border border-[#b42318] px-4 font-bold text-[#b42318] hover:bg-paper">Ya, hapus {{ Str::lower($label) }}</button>
            </form>
        </details>
    @endif
@endsection
