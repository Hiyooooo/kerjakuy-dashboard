@extends('layouts.admin')

@section('title', 'Role')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Role</h1>
        <a href="{{ route('admin.roles.create') }}" class="inline-flex min-h-12 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Tambah role</a>
    </div>

    @if (session('role_status'))
        <p role="status" class="mt-5 rounded-[10px] border border-line bg-white px-4 py-3 text-[#26734d]">{{ session('role_status') }}</p>
    @endif

    @if ($roles->isEmpty())
        <p class="mt-6 rounded-[22px] border border-line bg-white p-6 text-muted">Belum ada role di halaman ini.</p>
    @else
        <div class="mt-6 overflow-x-auto rounded-[22px] border border-line bg-white focus-visible:outline-offset-[-4px]" role="region" aria-label="Daftar role" tabindex="0">
            <table class="w-full min-w-[32rem] text-left text-sm">
                <thead class="border-b border-line bg-paper">
                    <tr>
                        <th scope="col" class="px-5 py-3">Role</th>
                        <th scope="col" class="px-5 py-3 text-right">Pengguna</th>
                        <th scope="col" class="px-5 py-3 text-right">Permission</th>
                        <th scope="col" class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($roles as $role)
                        <tr>
                            <th scope="row" class="px-5 py-3 font-bold break-all">
                                {{ $role->name }}
                                @if ($role->description)
                                    <span class="mt-1 block max-w-xs font-normal break-words text-muted">{{ Str::limit($role->description, 120) }}</span>
                                @endif
                            </th>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $role->users_count }}</td>
                            <td class="px-5 py-3 text-right tabular-nums">{{ $role->permissions_count }}</td>
                            <td class="px-5 py-3">
                                @if ($role->isSystemRole())
                                    <span class="text-muted">Role bawaan</span>
                                @else
                                    <a href="{{ route('admin.roles.edit', $role) }}" aria-label="Edit {{ $role->name }}" class="inline-flex min-h-11 items-center rounded-[10px] px-2 font-bold underline decoration-control/50 underline-offset-4 hover:bg-paper">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($roles->hasPages())
        <div class="mt-5">{{ $roles->links() }}</div>
    @endif
@endsection
