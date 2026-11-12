@extends('layouts.admin')

@php($editing = $account->exists)
@section('title', $editing ? 'Edit pengguna' : 'Tambah pengguna')

@section('content')
    <a href="{{ route('admin.accounts.index') }}" class="inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4">Kembali ke pengguna</a>
    <h1 class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">@yield('title')</h1>

    <form method="POST" action="{{ $editing ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}" class="mt-6 grid max-w-xl gap-5 rounded-[22px] border border-line bg-white p-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        @foreach (['name' => ['Nama', 'text', 'name'], 'email' => ['Email', 'email', 'email'], 'password' => ['Kata sandi', 'password', 'new-password'], 'password_confirmation' => ['Konfirmasi kata sandi', 'password', 'new-password']] as $field => [$label, $type, $autocomplete])
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" autocomplete="{{ $autocomplete }}"
                    @if ($type !== 'password') value="{{ old($field, $account->{$field}) }}" maxlength="150" @endif
                    @required(! $editing || $type !== 'password')
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @if ($field === 'password' && $editing)
                    <p class="mt-1 text-sm text-muted">Kosongkan untuk memakai kata sandi saat ini.</p>
                @endif
                @error($field)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endforeach

        <div>
            <label for="status" class="block text-sm font-bold">Status</label>
            <select id="status" name="status" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                @error('status') aria-invalid="true" aria-describedby="status-error" @enderror>
                @foreach (\App\Models\User::ACCOUNT_STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', $account->status ?? 'active') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('status')
                <p id="status-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="min-h-12 w-fit cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Simpan pengguna</button>
    </form>

    @if ($editing)
        <details class="mt-4 max-w-xl rounded-[22px] border border-line bg-white px-6 py-2">
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
