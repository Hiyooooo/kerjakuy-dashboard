@extends('layouts.admin')

@php
    $editing = $record->exists;
    $shiftForm = $resource === 'work-shifts';
    $title = ($editing ? 'Edit ' : 'Tambah ').Str::lower($label);
    $index = route('admin.'.$resource.'.index');
    $form = $editing ? route('admin.'.$resource.'.edit', $record) : route('admin.'.$resource.'.create');
    $token = request()->query('form');
    $restore = is_string($token) && old('_scheduling_token') === $token && old('_scheduling_form') === $form;
    $formValue = fn (string $field, mixed $default) => $restore ? old($field, $default) : $default;
    $errors = $restore ? $errors : new \Illuminate\Support\ViewErrorBag;
    $referenceField = $shiftForm ? 'attendance_time_id' : 'office_id';
    $selected = $formValue($referenceField, $record->{$referenceField});
    $fields = $shiftForm ? ['name' => ['Nama shift', 100], 'code' => ['Kode shift', 30]] : ['name' => ['Nama template', 120]];
    $version = $editing ? ($restore ? old('edit_version') : $record->editVersion()) : null;
@endphp

@section('title', $title)

@section('content')
    <a href="{{ $index }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke {{ Str::lower($label) }}</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $title }}</h1>

    <form method="POST" action="{{ $editing ? route('admin.'.$resource.'.update', $record) : route('admin.'.$resource.'.store') }}" class="mt-6 grid max-w-xl gap-5 rounded-[22px] border border-line bg-white p-5 sm:p-6">
        @csrf
        @if ($editing)
            @method('PUT')
                <input type="hidden" name="edit_version" value="{{ is_string($version) ? $version : '' }}">
                @error('edit_version')
                    <div class="text-sm text-[#b42318]">
                        <p role="alert">{{ $message }}</p>
                        <a href="{{ $form }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                    </div>
                @enderror
        @endif

        @foreach ($fields as $field => [$fieldLabel, $limit])
            @php($value = $formValue($field, $record->{$field}))
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $fieldLabel }}</label>
                <input id="{{ $field }}" name="{{ $field }}" value="{{ is_scalar($value) ? $value : '' }}" maxlength="{{ $limit }}" required
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error($field)<p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            </div>
        @endforeach

        <div>
            <label for="{{ $referenceField }}" class="block text-sm font-bold">{{ $shiftForm ? 'Waktu absen' : 'Kantor (opsional)' }}</label>
            <select id="{{ $referenceField }}" name="{{ $referenceField }}" @required($shiftForm) class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                @error($referenceField) aria-invalid="true" aria-describedby="reference-error" @enderror>
                <option value="">{{ $shiftForm ? 'Pilih waktu absen' : 'Lintas kantor' }}</option>
                @foreach ($options as $option)
                    <option value="{{ $option->id }}" @selected(is_scalar($selected) && (string) $selected === (string) $option->id)>{{ $option->name }}{{ $option->is_active ? '' : ' · Nonaktif' }}</option>
                @endforeach
            </select>
            @error($referenceField)<p id="reference-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            @if ($shiftForm && $options->isEmpty())
                <a href="{{ route('admin.attendance-times.create') }}" class="inline-flex min-h-11 items-center font-bold underline">Buat waktu absen terlebih dahulu</a>
            @endif
        </div>

        @unless ($shiftForm)
            @include('admin.scheduling.template-fields')
        @endunless

        <div>
            <label for="is_active" class="block text-sm font-bold">Status</label>
            <select id="is_active" name="is_active" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                @error('is_active') aria-invalid="true" aria-describedby="status-error" @enderror>
                <option value="1" @selected(in_array($formValue('is_active', $record->is_active ?? true), [true, 1, '1'], true))>Aktif</option>
                <option value="0" @selected(in_array($formValue('is_active', $record->is_active), [false, 0, '0'], true))>Nonaktif</option>
            </select>
            @error('is_active')<p id="status-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t border-line pt-4">
            <button type="submit" class="min-h-12 cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Simpan {{ Str::lower($label) }}</button>
            <a href="{{ $index }}" class="inline-flex min-h-12 items-center rounded-[10px] px-4 font-bold hover:bg-paper">Batal</a>
        </div>
    </form>
@endsection
