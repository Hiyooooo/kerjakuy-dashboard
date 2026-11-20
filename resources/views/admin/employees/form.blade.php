@extends('layouts.admin')

@php
    $editing = $employee->exists;
    $title = $editing ? 'Edit data pegawai' : 'Tambah pegawai';
    $form = 'employee_'.($employee->getKey() ?? 'create');
    $restoreInput = old('_employee_form') === $form;
    $editVersion = $restoreInput ? old('edit_version') : ($editing ? $employee->editVersion() : null);
    $fields = [
        'employee_code' => ['Kode pegawai', 'text', 30, true],
        'full_name' => ['Nama lengkap', 'text', 150, true],
        'email' => ['Email', 'email', 150, false],
        'phone' => ['Nomor telepon', 'tel', 30, false],
        'birth_date' => ['Tanggal lahir', 'date', null, false],
        'address' => ['Alamat', 'textarea', 2000, false],
    ];
@endphp

@section('title', $title)

@section('content')
    <div @class(['admin-form-column' => ! $editing])>
        <a href="{{ route('admin.employees.index') }}" class="inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4">Kembali ke data pegawai</a>
        <h1 data-enter class="mt-2 admin-title">{{ $title }}</h1>
        @if ($editing)
            <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
            @include('admin.employees.profile-nav')
            @if (session('employee_created_id') === $employee->getKey())
                <div class="mt-4 max-w-xl rounded-[10px] border border-line bg-white px-4 py-3">
                    <p role="status" class="font-bold text-[#26734d]">Profil pegawai berhasil dibuat.</p>
                    <p class="mt-1 text-sm text-muted">Lengkapi data sekarang atau kembali nanti.</p>
                    <a href="{{ route('admin.employees.identity.edit', $employee) }}" class="mt-1 inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4 hover:decoration-control">Lengkapi identitas</a>
                </div>
            @endif
        @else
            <p class="mt-2 max-w-xl text-muted">Isi data dasar untuk membuat profil. Setelah disimpan, Anda bisa melengkapi identitas, kepegawaian, penempatan, dan referensi wajah.</p>
        @endif

        <form method="POST" action="{{ $editing ? route('admin.employees.update', $employee) : route('admin.employees.store') }}" class="mt-6 grid gap-5 admin-panel admin-form">
            @csrf
            @if ($editing)
                @method('PUT')
                <input name="edit_version" type="hidden" value="{{ is_string($editVersion) ? $editVersion : '' }}">
                @error('edit_version', $form)
                    <div class="text-sm text-[#b42318]">
                        <p role="alert">{{ $message }}</p>
                        <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                    </div>
                @enderror
            @endif

            <div @class(['grid gap-5', 'lg:grid-cols-2' => $editing])>
                @foreach ($fields as $field => [$label, $type, $limit, $required])
                    @php
                        $default = $field === 'birth_date' ? $employee->birth_date?->toDateString() : $employee->{$field};
                        $value = $restoreInput ? old($field, $default) : $default;
                        $value = is_string($value) ? $value : '';
                    @endphp
                    <div @class(['min-w-0', 'lg:col-span-2' => $editing && $type === 'textarea'])>
                        <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}{{ $required ? '' : ' (opsional)' }}</label>
                        @if ($type === 'textarea')
                            <textarea id="{{ $field }}" name="{{ $field }}" rows="3" maxlength="{{ $limit }}"
                                @error($field, $form) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                                class="mt-1 w-full rounded-[10px] border border-control px-3 py-2">{{ $value }}</textarea>
                        @else
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" value="{{ $value }}"
                                @if ($limit) maxlength="{{ $limit }}" @endif
                                @if ($type === 'date') min="0001-01-01" max="{{ today('Asia/Jakarta')->toDateString() }}" @endif
                                @if ($required) required @endif
                                @error($field, $form) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                                class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                        @endif
                        @error($field, $form)
                            <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            @include('admin.employees.form-actions', ['submitLabel' => $editing ? 'Simpan pegawai' : 'Buat profil pegawai', 'cancelUrl' => route('admin.employees.index')])
        </form>
    </div>
@endsection
