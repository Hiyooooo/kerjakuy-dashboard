@extends('layouts.admin')

@php
    $editing = $period->exists;
    $title = $editing ? 'Edit periode kepegawaian' : 'Tambah periode kepegawaian';
    $form = $editing ? route('admin.employees.periods.edit', [$employee, $period]) : route('admin.employees.periods.create', $employee);
    $restore = $errors->getBag($form)->any();
    $version = $restore ? old('edit_version') : ($editing ? $period->editVersion() : null);
@endphp

@section('title', $title)

@section('content')
    <a href="{{ route('admin.employees.periods.index', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke riwayat kepegawaian</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $title }}</h1>
    <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>

    <form method="POST" action="{{ $editing ? route('admin.employees.periods.update', [$employee, $period]) : route('admin.employees.periods.store', $employee) }}" class="mt-6 grid max-w-xl gap-5 rounded-[22px] border border-line bg-white p-6">
        @csrf
        @if ($editing)
            @method('PUT')
            <input type="hidden" name="edit_version" value="{{ is_string($version) ? $version : '' }}">
            @error('edit_version', $form)
                <p role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>
                <a href="{{ route('admin.employees.periods.edit', [$employee, $period]) }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
            @enderror
        @endif

        @foreach (['employment_type' => ['Jenis hubungan kerja', $period::TYPES], 'status' => ['Status kepegawaian', $period::STATUSES]] as $field => [$label, $options])
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                <select id="{{ $field }}" name="{{ $field }}" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                    @error($field, $form) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                    <option value="">Pilih {{ Str::lower($label) }}</option>
                    @foreach ($options as $value => $option)
                        <option value="{{ $value }}" @selected(($restore ? old($field, $period->{$field}) : $period->{$field}) === $value)>{{ $option }}</option>
                    @endforeach
                </select>
                @error($field, $form)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        @foreach (['effective_start_date' => 'Berlaku mulai', 'effective_end_date' => 'Berlaku sampai (opsional)'] as $field => $label)
            @php($value = $restore ? old($field, $period->{$field}?->toDateString()) : $period->{$field}?->toDateString())
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                @if ($field === 'effective_end_date')
                    <p id="period-end-hint" class="mt-1 text-sm text-muted">Tanggal akhir ikut dihitung. Kosongkan jika belum ada tanggal akhir.</p>
                @endif
                <input id="{{ $field }}" name="{{ $field }}" type="date" min="0001-01-01" value="{{ is_string($value) ? $value : '' }}"
                    @required($field === 'effective_start_date')
                    aria-describedby="{{ $field === 'effective_end_date' ? 'period-end-hint' : '' }} @error($field, $form) {{ $field }}-error @enderror"
                    @error($field, $form) aria-invalid="true" @enderror class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error($field, $form)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div>
            @php($reason = $restore ? old('reason', $period->reason) : $period->reason)
            <label for="reason" class="block text-sm font-bold">Alasan (opsional)</label>
            <textarea id="reason" name="reason" rows="3" maxlength="2000" class="mt-1 w-full rounded-[10px] border border-control px-3 py-2"
                @error('reason', $form) aria-invalid="true" aria-describedby="reason-error" @enderror>{{ is_string($reason) ? $reason : '' }}</textarea>
            @error('reason', $form)
                <p id="reason-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="min-h-12 w-fit rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Simpan periode</button>
    </form>
@endsection
