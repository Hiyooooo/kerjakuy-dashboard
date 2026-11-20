@extends('layouts.admin')

@php
    $editing = $assignment->exists;
    $title = $editing ? 'Edit penempatan' : 'Tambah penempatan';
    $form = $editing ? route('admin.employees.assignments.edit', [$employee, $assignment]) : route('admin.employees.assignments.create', $employee);
    $restore = $errors->getBag($form)->any();
    $version = $restore ? old('edit_version') : ($editing ? $assignment->editVersion() : null);
@endphp

@section('title', $title)

@section('content')
    <div class="admin-form-column">
        <a href="{{ route('admin.employees.assignments.index', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke riwayat penempatan</a>
        <h1 data-enter class="mt-2 admin-title">{{ $title }}</h1>
        <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
        @include('admin.employees.profile-nav')

        <form method="POST" action="{{ $editing ? route('admin.employees.assignments.update', [$employee, $assignment]) : route('admin.employees.assignments.store', $employee) }}" class="mt-6 grid max-w-xl gap-5 admin-panel admin-form">
            @csrf
            @if ($editing)
                @method('PUT')
                <input type="hidden" name="edit_version" value="{{ is_string($version) ? $version : '' }}">
                @error('edit_version', $form)
                    <p role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>
                    <a href="{{ route('admin.employees.assignments.edit', [$employee, $assignment]) }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                @enderror
            @endif

            @foreach (['office_id' => ['Kantor', $offices], 'position_id' => ['Jabatan', $positions]] as $field => [$label, $options])
                <div>
                    <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                    <select id="{{ $field }}" name="{{ $field }}" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                        @error($field, $form) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        <option value="">Pilih {{ Str::lower($label) }}</option>
                        @foreach ($options as $option)
                            <option value="{{ $option->id }}" @selected((string) ($restore ? old($field, $assignment->{$field}) : $assignment->{$field}) === (string) $option->id)>{{ $option->name }} ({{ $option->code }}){{ $option->is_active ? '' : ' · Nonaktif' }}</option>
                        @endforeach
                    </select>
                    @error($field, $form)
                        <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div>
                <label for="change_type" class="block text-sm font-bold">Jenis perubahan</label>
                <p id="change-type-hint" class="mt-1 text-sm text-muted">Contoh: Penempatan awal, mutasi, atau promosi.</p>
                <input id="change_type" name="change_type" required maxlength="50"
                    value="{{ $restore ? old('change_type', $assignment->change_type) : $assignment->change_type }}"
                    aria-describedby="change-type-hint @error('change_type', $form) change-type-error @enderror"
                    @error('change_type', $form) aria-invalid="true" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error('change_type', $form)
                    <p id="change-type-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>

            @foreach (['effective_start_date' => 'Tanggal mulai berlaku', 'effective_end_date' => 'Tanggal akhir berlaku (opsional)'] as $field => $label)
                @php($value = $restore ? old($field, $assignment->{$field}?->toDateString()) : $assignment->{$field}?->toDateString())
                <div>
                    <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                    @if ($field === 'effective_start_date')
                        <p id="assignment-start-hint" class="mt-1 text-sm text-muted">Masukkan hari pertama periode berlaku.</p>
                    @else
                        <p id="assignment-end-hint" class="mt-1 text-sm text-muted">Periode tetap berlaku pada tanggal akhir. Kosongkan jika belum ada tanggal akhir.</p>
                    @endif
                    <input id="{{ $field }}" name="{{ $field }}" type="date" min="0001-01-01" value="{{ is_string($value) ? $value : '' }}"
                        @required($field === 'effective_start_date')
                        aria-describedby="{{ $field === 'effective_end_date' ? 'assignment-end-hint' : 'assignment-start-hint' }} @error($field, $form) {{ $field }}-error @enderror"
                        @error($field, $form) aria-invalid="true" @enderror class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                    @error($field, $form)
                        <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <div>
                @php($notes = $restore ? old('notes', $assignment->notes) : $assignment->notes)
                <label for="notes" class="block text-sm font-bold">Catatan (opsional)</label>
                <textarea id="notes" name="notes" rows="3" maxlength="2000" class="mt-1 w-full rounded-[10px] border border-control px-3 py-2"
                    @error('notes', $form) aria-invalid="true" aria-describedby="notes-error" @enderror>{{ is_string($notes) ? $notes : '' }}</textarea>
                @error('notes', $form)
                    <p id="notes-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
            @include('admin.employees.form-actions', ['submitLabel' => 'Simpan penempatan', 'cancelUrl' => route('admin.employees.assignments.index', $employee)])
        </form>
    </div>
@endsection
