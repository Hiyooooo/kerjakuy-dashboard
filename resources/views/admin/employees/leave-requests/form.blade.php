@extends('layouts.admin')
@php
    $title = $entitlement ? 'Tambahkan jatah cuti' : 'Buat permohonan cuti';
@endphp
@section('title', $title)

@section('content')
    <a href="{{ route('admin.leave-requests.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke permohonan cuti</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $title }}</h1>
    @if (session('leave_status'))
        <p role="status" class="mt-4 text-[#26734d]">{{ session('leave_status') }}</p>
    @endif
    @unless ($entitlement)
        <p class="mt-2 text-sm text-muted">Jatah dipilih otomatis berdasarkan pegawai, kategori, dan tahun tiap tanggal cuti.</p>
        <a href="{{ route('admin.leave-requests.entitlements.create') }}" class="inline-flex min-h-11 items-center text-sm font-bold underline">Tambahkan jatah cuti</a>
    @endunless
    <form method="POST" enctype="multipart/form-data" action="{{ route($entitlement ? 'admin.leave-requests.entitlements.store' : 'admin.leave-requests.store') }}"
        class="mt-4 grid max-w-xl gap-4 rounded-[22px] border border-line bg-white p-5 sm:p-6">
        @csrf
        @php
            $fields = ['employee_code' => ['Kode pegawai', 'text']];
            if ($entitlement) {
                $fields += [
                    'entitlement_year' => ['Tahun jatah', 'number'],
                    'granted_days' => ['Jatah cuti (hari)', 'number'],
                    'carry_over_days' => ['Sisa bawaan (hari, opsional)', 'number'],
                ];
            }
        @endphp
        @foreach ($fields as $field => [$label, $type])
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ is_scalar(old($field)) ? old($field) : '' }}"
                    @required($field !== 'carry_over_days')
                    @if ($field === 'employee_code') maxlength="30" @elseif ($field === 'entitlement_year') min="1" max="9999" @else min="0" max="999.99" step="0.01" @endif
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                @error($field)<p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <div>
            <label for="leave_category_id" class="block text-sm font-bold">Kategori cuti</label>
            <select id="leave_category_id" name="leave_category_id" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                @error('leave_category_id') aria-invalid="true" aria-describedby="category-error" @enderror>
                <option value="">Pilih kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $category->id === (is_scalar(old('leave_category_id')) ? (string) old('leave_category_id') : ''))>{{ $category->name }}</option>
                @endforeach
            </select>
            @error('leave_category_id')<p id="category-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
        </div>
        @unless ($entitlement)
            <fieldset data-leave-days class="min-w-0">
                <legend class="font-bold">Hari cuti</legend>
                <p class="mt-1 text-sm text-muted">Masukkan setiap tanggal yang diajukan. Gunakan 0,5 untuk setengah hari.</p>
                @error('days')<p class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
                <div data-leave-day-list class="mt-2 grid gap-3">
                    @foreach (is_array(old('days')) && old('days') ? array_slice(old('days'), 0, 366, true) : [[]] as $row => $day)
                        @include('admin.employees.leave-requests.day', compact('row', 'day'))
                    @endforeach
                </div>
                <template data-leave-day-template>
                    @include('admin.employees.leave-requests.day', ['row' => '__row__', 'day' => []])
                </template>
                <button type="button" data-add-leave-day hidden class="mt-2 min-h-11 px-2 text-sm font-bold underline">Tambah tanggal</button>
            </fieldset>
            <div>
                <label for="reason" class="block text-sm font-bold">Alasan (opsional)</label>
                <textarea id="reason" name="reason" rows="3" maxlength="2000" class="mt-1 w-full rounded-[10px] border border-control px-3 py-2">{{ is_string(old('reason')) ? old('reason') : '' }}</textarea>
                @error('reason')<p class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="attachment" class="block text-sm font-bold">Lampiran (opsional)</label>
                <input id="attachment" name="attachment" type="file" accept=".pdf,.jpg,.jpeg,.png" aria-describedby="attachment-help" class="mt-1 min-h-11 w-full text-sm">
                <p id="attachment-help" class="text-sm text-muted">PDF, JPG, atau PNG hingga 5 MB.</p>
                @error('attachment')<p class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            </div>
        @endunless
        <button type="submit" class="min-h-12 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 sm:justify-self-start">{{ $entitlement ? 'Simpan jatah cuti' : 'Simpan permohonan' }}</button>
    </form>
@endsection
