@extends('layouts.admin')

@section('title', 'Permission')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Permission</h1>
        <a href="{{ route('admin.permissions.create') }}" class="inline-flex min-h-12 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Tambah permission</a>
    </div>

    @if (session('permission_status'))
        <p role="status" class="mt-5 rounded-[10px] border border-line bg-white px-4 py-3 text-[#26734d]">{{ session('permission_status') }}</p>
    @endif

    @if ($permissions->isEmpty())
        <p class="mt-6 rounded-[22px] border border-line bg-white p-6 text-muted">Belum ada permission di halaman ini.</p>
    @else
        <div class="mt-6 overflow-x-auto rounded-[22px] border border-line bg-white focus-visible:outline-offset-[-4px]" role="region" aria-label="Daftar permission" tabindex="0">
            <table class="w-full table-fixed text-left text-sm">
                <thead class="border-b border-line bg-paper">
                    <tr>
                        <th scope="col" class="px-5 py-3">Permission</th>
                        <th scope="col" class="w-14 px-2 py-3 text-right">Role</th>
                        <th scope="col" class="w-24 px-3 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($permissions as $permission)
                        <tr>
                            <th scope="row" class="px-5 py-3 font-bold [overflow-wrap:anywhere]">
                                {{ $permission->name }}
                                @if ($permission->description)
                                    <span class="mt-1 block max-w-xs font-normal break-words text-muted">{{ Str::limit($permission->description, 120) }}</span>
                                @endif
                            </th>
                            <td class="px-2 py-3 text-right tabular-nums">{{ $permission->roles_count }}</td>
                            <td class="px-3 py-3">
                                @if ($permission->isProtected())
                                    <span class="text-muted">Terkunci</span>
                                @else
                                    <a href="{{ route('admin.permissions.edit', $permission) }}" aria-label="Edit {{ $permission->name }}" class="inline-flex min-h-11 items-center rounded-[10px] px-2 font-bold underline decoration-control/50 underline-offset-4 hover:bg-paper">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($permissions->hasPages())
        <div class="mt-5">{{ $permissions->links() }}</div>
    @endif
@endsection
