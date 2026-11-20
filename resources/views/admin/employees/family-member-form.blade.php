@extends('layouts.admin')

@php
    $editing = $familyMember->exists;
    $title = $editing ? 'Edit anggota keluarga' : 'Tambah anggota keluarga';
    $index = route('admin.employees.family-members.index', $employee);
    $form = $editing ? route('admin.employees.family-members.edit', [$employee, $familyMember]) : route('admin.employees.family-members.create', $employee);
    $restore = $errors->getBag($form)->any();
    $version = $restore ? old('edit_version') : ($editing ? $familyMember->editVersion() : null);
    $dependent = $restore ? old('is_tax_dependent') : (string) (int) $familyMember->is_tax_dependent;
@endphp

@section('title', $title)

@section('content')
    <div class="admin-form-column">
        <a href="{{ $index }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke keluarga pegawai</a>
        <h1 data-enter class="mt-2 admin-title">{{ $title }}</h1>
        <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
        @include('admin.employees.profile-nav')

        <form method="POST" action="{{ $editing ? route('admin.employees.family-members.update', [$employee, $familyMember]) : route('admin.employees.family-members.store', $employee) }}" class="mt-6 grid max-w-xl gap-5 admin-panel admin-form">
            @csrf
            @if ($editing)
                @method('PUT')
                <input type="hidden" name="edit_version" value="{{ is_string($version) ? $version : '' }}">
                @error('edit_version', $form)
                    <p role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>
                    <a href="{{ $form }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                @enderror
            @endif

            @foreach ([
                'full_name' => ['Nama lengkap', 'text', 150, true],
                'relationship' => ['Hubungan keluarga', 'text', 50, true],
                'birth_date' => ['Tanggal lahir (opsional)', 'date', null, false],
                'occupation' => ['Pekerjaan (opsional)', 'text', 100, false],
                'phone' => ['Nomor telepon (opsional)', 'tel', 30, false],
            ] as $field => [$label, $type, $length, $required])
                @php
                    $saved = $type === 'date' ? $familyMember->{$field}?->toDateString() : $familyMember->{$field};
                    $value = $restore ? old($field) : $saved;
                @endphp
                <div>
                    <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                    @if ($field === 'relationship')
                        <p id="relationship-hint" class="mt-1 text-sm text-muted">Contoh: Anak, pasangan, atau orang tua.</p>
                    @endif
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ is_string($value) ? $value : '' }}"
                        @required($required) @if ($length) maxlength="{{ $length }}" @endif
                        @if ($type === 'date') min="0001-01-01" max="{{ today('Asia/Jakarta')->toDateString() }}" @endif
                        aria-describedby="{{ $field === 'relationship' ? 'relationship-hint' : '' }} @error($field, $form) {{ $field }}-error @enderror"
                        @error($field, $form) aria-invalid="true" @enderror
                        class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                    @error($field, $form)
                        <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach

            <fieldset class="grid min-w-0 gap-4 border-t border-line pt-4" aria-describedby="tax-period-hint">
                <legend class="pr-2 font-bold">Tanggungan pajak</legend>
                <p id="tax-period-hint" class="text-sm text-muted">Isi tanggal mulai jika dicatat sebagai tanggungan. Untuk mengakhiri tanggungan dan menyimpan riwayatnya, isi tanggal akhir. Jika bukan tanggungan, kosongkan kedua tanggal.</p>
                @if ($familyMember->tax_dependent_end_date)
                    <label class="flex min-h-11 items-center gap-2 text-sm font-bold">
                        <input type="checkbox" name="start_new_tax_period" value="1"
                            @checked($restore && old('start_new_tax_period') === '1') aria-describedby="new-tax-period-hint">
                        Mulai periode baru
                    </label>
                    <p id="new-tax-period-hint" class="text-sm text-muted">
                        Pilih tanggal mulai setelah {{ $familyMember->tax_dependent_end_date->locale('id')->translatedFormat('j F Y') }} dan isi tanggal akhir yang baru jika ada. Periode sebelumnya akan masuk ke riwayat.
                    </p>
                    @error('start_new_tax_period', $form)
                        <p class="text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                @endif
                <div>
                    <label for="is_tax_dependent" class="block text-sm font-bold">Dicatat sebagai tanggungan</label>
                    <select id="is_tax_dependent" name="is_tax_dependent" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                        @error('is_tax_dependent', $form) aria-invalid="true" aria-describedby="is_tax_dependent-error" @enderror>
                        <option value="0" @selected($dependent === '0')>Tidak</option>
                        <option value="1" @selected($dependent === '1')>Ya</option>
                    </select>
                    @error('is_tax_dependent', $form)
                        <p id="is_tax_dependent-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach (['tax_dependent_start_date' => 'Tanggal mulai', 'tax_dependent_end_date' => 'Tanggal akhir (opsional)'] as $field => $label)
                        @php($value = $restore ? old($field) : $familyMember->{$field}?->toDateString())
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="date" min="0001-01-01" value="{{ is_string($value) ? $value : '' }}"
                                @error($field, $form) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                                class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                            @error($field, $form)
                                <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>
            @if ($pastPeriods->isNotEmpty())
                <section class="border-t border-line pt-4">
                    <h2 class="font-bold">Riwayat periode tanggungan</h2>
                    <ul class="mt-2 space-y-1 text-sm text-muted">
                        @foreach ($pastPeriods as $period)
                            <li>{{ \Carbon\CarbonImmutable::parse($period->start_date)->locale('id')->translatedFormat('j F Y') }}
                                sampai {{ \Carbon\CarbonImmutable::parse($period->end_date)->locale('id')->translatedFormat('j F Y') }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif
            @include('admin.employees.form-actions', ['submitLabel' => 'Simpan anggota keluarga', 'cancelUrl' => $index])
        </form>
    </div>
@endsection
