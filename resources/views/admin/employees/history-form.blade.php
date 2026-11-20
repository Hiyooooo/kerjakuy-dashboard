@extends('layouts.admin')

@php
    $editing = $record !== null;
    $label = mb_strtolower($history->label());
    $title = ($editing ? 'Edit ' : 'Tambah ').$label;
    $index = route($history->routeName('index'), $employee);
    $form = $editing ? route($history->routeName('edit'), [$employee, $record]) : route($history->routeName('create'), $employee);
    $restore = $errors->getBag($form)->any();
    $editVersion = $restore ? old('edit_version') : $version;
@endphp

@section('title', $title)

@section('content')
    <a href="{{ $index }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke {{ $label }}</a>
    <h1 data-enter class="mt-2 admin-title">{{ $title }}</h1>
    <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
    @include('admin.employees.profile-nav')

    <form method="POST" action="{{ $editing ? route($history->routeName('update'), [$employee, $record]) : route($history->routeName('store'), $employee) }}"
        @if ($history === \App\Employee\EmployeeHistory::Training) enctype="multipart/form-data" @endif
        class="mt-6 grid gap-5 admin-panel admin-form">
        @csrf
        @if ($editing)
            @method('PUT')
            <input type="hidden" name="edit_version" value="{{ is_string($editVersion) ? $editVersion : '' }}">
            @error('edit_version', $form)
                <div class="text-sm text-[#b42318]">
                    <p role="alert">{{ $message }}</p>
                    <a href="{{ $form }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                </div>
            @enderror
        @endif

        <div class="grid max-w-3xl gap-5 sm:grid-cols-2">
            @foreach ($history->fields() as $field => [$fieldLabel, $type, $limit, $required])
                @php
                    $saved = $record?->{$field};
                    $value = $restore ? old($field) : ($type === 'date' ? $saved?->toDateString() : $saved);
                    $value = is_scalar($value) ? $value : '';
                @endphp
                <div @class(['min-w-0', 'sm:col-span-2' => $type === 'textarea'])>
                    <label for="{{ $field }}" class="block text-sm font-bold">{{ $fieldLabel }}{{ $required ? '' : ' (opsional)' }}</label>
                    @if ($type === 'textarea')
                        <textarea id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="{{ $limit }}"
                            @error($field, $form) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                            class="mt-1 w-full rounded-[10px] border border-control px-3 py-2">{{ $value }}</textarea>
                    @else
                        <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ $value }}" @required($required)
                            @if ($limit) maxlength="{{ $limit }}" @endif
                            @if ($type === 'date') min="0001-01-01" max="9999-12-31" @endif
                            @if ($type === 'number') min="1" max="9999" step="1" @endif
                            @error($field, $form) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                            class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                    @endif
                    @error($field, $form)
                        <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>

        @if ($history === \App\Employee\EmployeeHistory::Training)
            <div class="max-w-xl">
                <label for="certificate" class="block text-sm font-bold">File sertifikat (opsional)</label>
                <p id="certificate-hint" class="mt-1 text-sm text-muted">PDF, JPG, atau PNG hingga 5 MB. {{ $record?->certificate_file_path ? 'Kosongkan untuk mempertahankan file saat ini.' : '' }}</p>
                @if ($record?->certificate_file_path)
                    <a href="{{ route($history->routeName('certificate'), [$employee, $record]) }}" class="inline-flex min-h-11 items-center text-sm font-bold underline">Unduh sertifikat saat ini</a>
                    <label class="flex min-h-11 items-center gap-2 text-sm font-bold">
                        <input type="checkbox" name="remove_certificate" value="1" @checked($restore && old('remove_certificate') === '1')>
                        Hapus sertifikat saat menyimpan
                    </label>
                    @error('remove_certificate', $form)
                        <p class="text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                @endif
                <input id="certificate" name="certificate" type="file" accept=".pdf,.jpg,.jpeg,.png"
                    aria-describedby="certificate-hint @error('certificate', $form) certificate-error @enderror"
                    @error('certificate', $form) aria-invalid="true" @enderror
                    class="mt-2 min-h-12 w-full rounded-[10px] border border-control text-sm file:mr-2 file:min-h-12 file:bg-paper file:px-3 file:font-bold">
                @error('certificate', $form)
                    <p id="certificate-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @include('admin.employees.form-actions', ['submitLabel' => 'Simpan '.$label, 'cancelUrl' => $index])
    </form>
@endsection
