@extends('layouts.admin')

@php($editing = $account->exists)
@section('title', $editing ? 'Edit pengguna' : 'Tambah pengguna')

@section('breadcrumb-parent')
    <a href="{{ route('admin.accounts.index') }}" class="inline-flex min-h-11 items-center hover:text-ink hover:underline">Pengguna</a>
@endsection

@section('content')
    <h1 data-enter class="mx-auto max-w-xl font-heading text-3xl leading-tight font-extrabold tracking-tight sm:text-[40px]">@yield('title')</h1>

    <form method="POST" action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}" class="mx-auto mt-6 grid max-w-xl gap-6 rounded-[22px] border border-line bg-white p-4 sm:p-6">
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
                                @if ($type !== 'password') value="{{ old($field, $account->{$field}) }}" maxlength="150" @endif
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

        <div>
            <label for="role_id" class="block text-sm font-bold">Role</label>
            <select id="role_id" name="role_id" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                @error('role_id') aria-invalid="true" aria-describedby="role-error" @enderror>
                <option value="">Pilih role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" @selected((string) old('role_id', $account->role_id) === (string) $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
            @error('role_id')
                <p id="role-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status" class="block text-sm font-bold">Status akun</label>
            <select id="status" name="status" required class="mt-1 min-h-12 w-full rounded-[10px] border border-[#8b807b] px-3 aria-invalid:border-[#b42318]"
                @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                @foreach (\App\Models\User::ACCOUNT_STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $account->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')
                <p id="status-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex flex-wrap items-center gap-2 border-t border-line pt-4">
            <button type="submit" class="min-h-12 cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">Simpan pengguna</button>
            <a href="{{ route('admin.accounts.index') }}" class="inline-flex min-h-12 items-center rounded-[10px] px-4 font-bold hover:bg-paper">Batal</a>
        </div>
    </form>

    @if ($editing)
        <details class="mx-auto mt-4 max-w-xl rounded-[22px] border border-line bg-white px-6 py-2">
            <summary class="min-h-11 cursor-pointer py-3 font-bold text-[#b42318]">Hapus pengguna</summary>
            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" class="space-y-3 pb-4">
                @csrf
                @method('DELETE')
                <p>Hapus akun {{ $account->email }} secara permanen?</p>
                <button type="submit" class="min-h-11 cursor-pointer rounded-[10px] border border-[#b42318] px-4 font-bold text-[#b42318] hover:bg-paper">Ya, hapus pengguna</button>
            </form>
        </details>
    @endif
@endsection
