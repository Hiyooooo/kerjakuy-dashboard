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
        'minimum_match_score' => ['Skor minimum kecocokan', 'number', $policy->minimum_match_score, 'Masukkan skor dari 0 sampai 100. Skor yang sama dengan atau lebih tinggi dari batas ini memenuhi syarat kecocokan.'],
        'effective_start_date' => ['Berlaku mulai', 'date', $policy->effective_start_date?->toDateString(), null],
        'effective_end_date' => ['Berlaku sampai (opsional)', 'date', $policy->effective_end_date?->toDateString(), 'Tanggal akhir ikut dihitung. Kosongkan jika kebijakan berlaku tanpa batas akhir.'],
    ];
@endphp

@section('title', $title)

@section('content')
    <a href="{{ route('admin.face-tolerance-policies.index') }}" class="inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4">Kembali ke toleransi wajah</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $title }}</h1>

    @if ($previousPolicy)
        <p class="mt-2 max-w-xl text-muted">Kebijakan yang dimulai {{ $previousPolicy->effective_start_date->locale('id')->translatedFormat('j F Y') }} akan ditutup sehari sebelum penggantinya mulai berlaku.</p>
    @elseif ($editing && ($policy->effective_end_date === null || $policy->effective_end_date->toDateString() >= today('Asia/Jakarta')->toDateString()))
        <a href="{{ route('admin.face-tolerance-policies.replacement', $policy) }}" class="mt-2 inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4">Jadwalkan pengganti</a>
    @endif

    <form method="POST" action="{{ $action }}" class="mt-6 grid max-w-xl gap-5 rounded-[22px] border border-line bg-white p-6">
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

        <button type="submit" class="min-h-12 w-fit cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">{{ $previousPolicy ? 'Jadwalkan pengganti' : 'Simpan kebijakan' }}</button>
    </form>
@endsection
