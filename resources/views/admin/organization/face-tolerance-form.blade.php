@extends('layouts.admin')

@php
    $editing = $policy->exists;
    $previousPolicy ??= null;
    $versionedPolicy = $previousPolicy ?? ($editing ? $policy : null);
    $title = match (true) {
        $previousPolicy !== null => 'Jadwalkan pengganti',
        $editing => 'Edit kebijakan toleransi wajah',
        default => 'Tambah kebijakan toleransi wajah',
    };
    $action = match (true) {
        $previousPolicy !== null => route('admin.face-tolerance-policies.replace', $previousPolicy),
        $editing => route('admin.face-tolerance-policies.update', $policy),
        default => route('admin.face-tolerance-policies.store'),
    };
    $editVersion = session()->hasOldInput() ? old('edit_version') : $versionedPolicy?->editVersion();
    $fields = [
        'minimum_match_score' => ['Skor minimum kecocokan', 'number', $policy->minimum_match_score, 'Masukkan skor dari 0 sampai 100. Menaikkan skor minimum memperketat verifikasi. Jika batas naik dari 85 ke 90, skor 87 tidak lagi lolos.'],
        'effective_start_date' => ['Berlaku mulai', 'date', $policy->effective_start_date?->toDateString(), null],
        'effective_end_date' => ['Berlaku sampai (opsional)', 'date', $policy->effective_end_date?->toDateString(), 'Tanggal akhir ikut dihitung. Kosongkan jika kebijakan berlaku tanpa batas akhir.'],
    ];
@endphp

@section('title', $title)

@section('breadcrumb-parent')
    <a href="{{ route('admin.face-tolerance-policies.index') }}" class="inline-flex min-h-11 items-center hover:text-ink hover:underline">Toleransi wajah</a>
@endsection

@section('content')
    <div class="mx-auto max-w-xl">
    <h1 data-enter class="admin-title">{{ $title }}</h1>

    @if ($previousPolicy)
        <p class="mt-2 max-w-xl text-muted">Kebijakan yang dimulai {{ $previousPolicy->effective_start_date->locale('id')->translatedFormat('j F Y') }} akan ditutup sehari sebelum penggantinya mulai berlaku.</p>
    @elseif ($editing)
        <p class="mt-2 max-w-xl text-muted"><strong>Edit kebijakan</strong> untuk mengoreksi skor atau tanggal pada kebijakan ini.</p>
        @if ($policy->effective_end_date === null || $policy->effective_end_date->toDateString() >= today('Asia/Jakarta')->toDateString())
            <div class="mt-1 max-w-xl text-muted">
                <a href="{{ route('admin.face-tolerance-policies.replacement', $policy) }}" class="inline-flex min-h-11 items-center font-bold text-ink underline decoration-control/50 underline-offset-4">Jadwalkan pengganti</a>
                <p>Buat kebijakan baru untuk tanggal mendatang. Kebijakan lama berakhir sehari sebelum penggantinya mulai.</p>
            </div>
        @endif
    @endif

    <form method="POST" action="{{ $action }}" class="mt-6 grid max-w-xl gap-5 admin-panel admin-form">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif
        @if ($versionedPolicy)
            <input name="edit_version" type="hidden" value="{{ is_string($editVersion) ? $editVersion : '' }}">
            @error('edit_version')
                <div class="text-sm text-[#b42318]">
                    <p role="alert">{{ $message }}</p>
                    <a href="{{ route($previousPolicy ? 'admin.face-tolerance-policies.replacement' : 'admin.face-tolerance-policies.edit', $versionedPolicy) }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                </div>
            @enderror
        @endif

        @foreach ($fields as $field => [$label, $type, $default, $hint])
            @php
                $value = old($field, $default);
                $description = trim(($hint ? $field.'-hint ' : '').($errors->has($field) ? $field.'-error' : ''));
            @endphp
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                @if ($hint)
                    <p id="{{ $field }}-hint" class="mt-1 text-sm text-muted">{{ $hint }}</p>
                @endif
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ is_scalar($value) ? $value : '' }}"
                    @if ($type === 'number') min="0" max="100" step="0.01" @endif
                    @if ($field !== 'effective_end_date') required @endif
                    @if ($description !== '') aria-describedby="{{ $description }}" @endif
                    @error($field) aria-invalid="true" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error($field)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div class="admin-actions">
            <button type="submit" class="admin-button admin-button--primary">{{ $previousPolicy ? 'Jadwalkan pengganti' : 'Simpan kebijakan' }}</button>
            <a href="{{ route('admin.face-tolerance-policies.index') }}" class="admin-button admin-button--quiet">Batal</a>
        </div>
    </form>
    </div>
@endsection
