@extends('layouts.admin')

@php($editing = $permission->exists)
@section('title', $editing ? 'Edit permission' : 'Tambah permission')

@section('content')
    <a href="{{ route('admin.permissions.index') }}" class="inline-flex min-h-11 items-center font-bold underline decoration-control/50 underline-offset-4">Kembali ke permission</a>
    <h1 class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">@yield('title')</h1>

    <form method="POST" action="{{ $editing ? route('admin.permissions.update', $permission) : route('admin.permissions.store') }}" class="mt-6 grid max-w-xl gap-5 rounded-[22px] border border-line bg-white p-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <div>
            <label for="name" class="block text-sm font-bold">Nama permission</label>
            <input id="name" name="name" value="{{ old('name', $permission->name) }}" maxlength="100" required
                @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
                class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
            @error('name')
                <p id="name-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-bold">Deskripsi (opsional)</label>
            <textarea id="description" name="description" rows="4" maxlength="2000"
                @error('description') aria-invalid="true" aria-describedby="description-error" @enderror
                class="mt-1 w-full rounded-[10px] border border-control px-3 py-2">{{ old('description', $permission->description) }}</textarea>
            @error('description')
                <p id="description-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="min-h-12 w-fit cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Simpan permission</button>
    </form>

    @if ($editing)
        <details class="mt-4 max-w-xl rounded-[22px] border border-line bg-white px-6 py-2">
            <summary class="min-h-11 cursor-pointer py-3 font-bold text-[#b42318]">Hapus permission</summary>
            <form method="POST" action="{{ route('admin.permissions.destroy', $permission) }}" class="space-y-3 pb-4">
                @csrf
                @method('DELETE')
                <p>Hapus permission {{ $permission->name }} secara permanen?</p>
                <button type="submit" class="min-h-11 cursor-pointer rounded-[10px] border border-[#b42318] px-4 font-bold text-[#b42318] hover:bg-paper">Ya, hapus permission</button>
            </form>
        </details>
    @endif
@endsection
