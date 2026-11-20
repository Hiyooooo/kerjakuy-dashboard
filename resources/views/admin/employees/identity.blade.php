@extends('layouts.admin')

@section('title', 'Identitas pegawai')

@section('content')
    <div>
        @php
            $form = route('admin.employees.identity.edit', $employee);
            $editVersion = $errors->getBag($form)->any() ? old('edit_version') : ($identity?->editVersion() ?? 'new');
        @endphp
        <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke data pegawai</a>
        <h1 data-enter class="mt-2 admin-title">Identitas pegawai</h1>
        <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
        @include('admin.employees.profile-nav')
        @if (session('employee_status'))
            <p role="status" class="mt-4 text-[#26734d]">{{ session('employee_status') }}</p>
        @endif

        <form method="POST" action="{{ route('admin.employees.identity.update', $employee) }}" class="mt-6 grid gap-5 admin-panel admin-form">
            @csrf
            @method('PUT')
            <input type="hidden" name="edit_version" value="{{ is_string($editVersion) ? $editVersion : '' }}">
            @error('edit_version', $form)
                <div class="text-sm text-[#b42318]">
                    <p role="alert">{{ $message }}</p>
                    <a href="{{ $form }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                </div>
            @enderror
            <div class="max-w-xl">
                <label for="gender" class="block text-sm font-bold">Jenis kelamin</label>
                <select id="gender" name="gender" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                    @error('gender', $form) aria-invalid="true" aria-describedby="gender-error" @enderror>
                    <option value="">Pilih jenis kelamin</option>
                    <option value="male" @selected(($errors->getBag($form)->any() ? old('gender') : $identity?->gender) === 'male')>Laki-laki</option>
                    <option value="female" @selected(($errors->getBag($form)->any() ? old('gender') : $identity?->gender) === 'female')>Perempuan</option>
                </select>
                @error('gender', $form)
                    <p id="gender-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
            <div class="max-w-xl">
                <label for="national_id" class="block text-sm font-bold">{{ $identity ? 'NIK baru (opsional)' : 'NIK' }}</label>
                <p id="nik-hint" class="mt-1 text-sm text-muted">{{ $identity ? 'NIK sudah tersimpan. Kosongkan untuk mempertahankannya.' : 'Masukkan 16 digit NIK pegawai.' }}</p>
                <input id="national_id" name="national_id" type="text" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" autocomplete="off" @required(! $identity)
                    aria-describedby="nik-hint @error('national_id', $form) nik-error @enderror"
                    @error('national_id', $form) aria-invalid="true" @enderror class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error('national_id', $form)
                    <p id="nik-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
            @include('admin.employees.form-actions', ['submitLabel' => 'Simpan identitas', 'cancelUrl' => route('admin.employees.edit', $employee)])
        </form>
    </div>
@endsection
