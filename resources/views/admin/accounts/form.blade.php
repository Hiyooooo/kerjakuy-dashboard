@extends('layouts.admin')

@php
    $editing = $account->exists;
    $formToken = request()->query('form');
    $restore = is_string($formToken)
        && old('_account_form_token') === $formToken
        && old('_account_form') === 'account_'.($account->getKey() ?? 'create');
    $formValue = function (string $field, mixed $default) use ($restore): mixed {
        $value = $restore ? old($field, $default) : $default;

        return is_scalar($value) ? $value : null;
    };
    $deletionErrors = $errors->getBag('accountDeletion');
    $errors = $restore ? $errors : new \Illuminate\Support\ViewErrorBag;
@endphp
@section('title', $editing ? 'Edit akun' : 'Tambah akun')

@section('breadcrumb-parent')
    <a href="{{ route('admin.accounts.index') }}" class="inline-flex min-h-11 items-center hover:text-ink hover:underline">Akses pengguna</a>
@endsection

@section('content')
    <h1 data-enter class="mx-auto max-w-xl admin-title">@yield('title')</h1>

    <form method="POST" action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}" class="mx-auto mt-6 grid max-w-xl gap-6 admin-panel admin-form">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        @foreach ([
            'Identitas akun' => ['name' => ['Nama', 'text', 'name'], 'email' => ['Email', 'email', 'email']],
            'Akses masuk' => ['password' => ['Kata sandi', 'password', 'new-password'], 'password_confirmation' => ['Konfirmasi kata sandi', 'password', 'new-password']],
        ] as $group => $fields)
            <fieldset class="min-w-0">
                <legend class="mb-3 font-heading font-extrabold">{{ $group }}</legend>
                <div class="grid gap-3">
                    @foreach ($fields as $field => [$label, $type, $autocomplete])
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}"
                                @if ($type !== 'password') value="{{ $formValue($field, $account->{$field}) }}" maxlength="150" @endif
                                @required(! $editing || $type !== 'password')
                                @error($field) aria-invalid="true" @enderror
                                @if ($field === 'password' || $errors->has($field))
                                    aria-describedby="{{ trim(($field === 'password' ? 'password-hint ' : '').($errors->has($field) ? $field.'-error' : '')) }}"
                                @endif
                                class="mt-1 min-h-12 w-full rounded-[10px] border border-[#8b807b] px-3 aria-invalid:border-[#b42318]">
                            @if ($field === 'password')
                                <p id="password-hint" class="mt-1 text-sm text-muted">
                                    Minimal 12 karakter. @if ($editing) Kosongkan untuk memakai kata sandi saat ini. @endif
                                </p>
                            @endif
                            @error($field)
                                <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </fieldset>
        @endforeach

        <fieldset class="min-w-0">
            <legend class="mb-3 font-heading font-extrabold">Peran dan status akses</legend>
            <div class="grid gap-4">
                <div>
                    <label for="role_id" class="block text-sm font-bold">Peran akses</label>
                    <select id="role_id" name="role_id" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                        @error('role_id') aria-invalid="true" aria-describedby="role-error" @enderror>
                        <option value="">Pilih peran</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) $formValue('role_id', $account->role_id) === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id')
                        <p id="role-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                    @include('admin.accounts.role-help')
                </div>

                <div>
                    <label for="status" class="block text-sm font-bold">Status akun</label>
                    <select id="status" name="status" required class="mt-1 min-h-12 w-full rounded-[10px] border border-[#8b807b] px-3 aria-invalid:border-[#b42318]"
                        @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                        @foreach (\App\Access\Models\User::ACCOUNT_STATUSES as $value => $label)
                            <option value="{{ $value }}" @selected($formValue('status', $account->status ?? 'active') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')
                        <p id="status-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>

        <div>
            @php
                $selectedEmployee = (string) $formValue('employee_id', $account->employee_id);
                $employeeLinkVersion = $formValue('employee_link_version', $restore ? null : $account->employeeLinkVersion());
            @endphp
            @if ($editing)
                <input type="hidden" name="employee_link_version" value="{{ $employeeLinkVersion }}">
            @endif
            <label for="employee_id" class="block text-sm font-bold">Pegawai terhubung (opsional)</label>
            <p id="employee-link-hint" class="mt-1 text-sm text-muted">Satu pegawai hanya bisa terhubung ke satu akun. Pilih Tanpa pegawai untuk melepas tautan.</p>
            <select id="employee_id" name="employee_id" class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                aria-describedby="employee-link-hint @error('employee_id') employee-link-error @enderror"
                @error('employee_id') aria-invalid="true" @enderror>
                <option value="">Tanpa pegawai</option>
                @if ($selectedEmployee !== '' && ! $employees->contains(fn ($employee) => (string) $employee->id === $selectedEmployee))
                    <option value="{{ $selectedEmployee }}" selected>Pegawai yang dipilih tidak tersedia</option>
                @endif
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected($selectedEmployee === (string) $employee->id)>{{ $employee->full_name }} ({{ $employee->employee_code }})</option>
                @endforeach
            </select>
            @error('employee_id')
                <p id="employee-link-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
            @error('employee_link_version')
                <p role="alert" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                <a href="{{ route('admin.accounts.edit', $account) }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
            @enderror
        </div>

        <div class="admin-actions">
            <button type="submit" class="admin-button admin-button--primary">Simpan akun</button>
            <a href="{{ route('admin.accounts.index') }}" class="admin-button admin-button--quiet">Batal</a>
        </div>
    </form>

    @if ($editing)
        <details class="mx-auto mt-4 max-w-xl admin-panel px-6 py-2" @if ($deletionErrors->any()) open @endif>
            <summary class="min-h-11 cursor-pointer py-3 font-bold text-[#b42318]">Hapus akun</summary>
            @if ($deletionErrors->any())
                <p role="alert" class="pb-3 text-sm text-[#b42318]">{{ $deletionErrors->first() }}</p>
            @endif
            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" class="space-y-3 pb-4">
                @csrf
                @method('DELETE')
                <p>Hapus akun {{ $account->email }} secara permanen?</p>
                <button type="submit" class="min-h-11 cursor-pointer rounded-[10px] border border-[#b42318] px-4 font-bold text-[#b42318] hover:bg-paper">Ya, hapus akun</button>
            </form>
        </details>
    @endif
@endsection
