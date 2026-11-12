@extends('layouts.admin')

@section('title', 'Pengguna')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Pengguna</h1>
        <a href="{{ route('admin.accounts.create') }}" class="inline-flex min-h-12 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Tambah pengguna</a>
    </div>

    @if (session('account_status'))
        <p role="status" class="mt-5 rounded-[10px] border border-line bg-white px-4 py-3 text-[#26734d]">{{ session('account_status') }}</p>
    @endif

    @if ($accounts->isEmpty())
        <p class="mt-6 rounded-[22px] border border-line bg-white p-6 text-muted">Belum ada pengguna di halaman ini.</p>
    @else
        <div class="mt-6 overflow-x-auto rounded-[22px] border border-line bg-white focus-visible:outline-offset-[-4px]" role="region" aria-label="Daftar pengguna" tabindex="0">
            <table class="w-full min-w-[34rem] text-left text-sm">
                <thead class="border-b border-line bg-paper">
                    <tr>
                        <th scope="col" class="px-5 py-3">Pengguna</th>
                        <th scope="col" class="px-5 py-3">Status</th>
                        <th scope="col" class="px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($accounts as $account)
                        <tr>
                            <th scope="row" class="px-5 py-3 font-bold">
                                {{ $account->name }}
                                <span class="block font-normal break-all text-muted">{{ $account->email }}</span>
                            </th>
                            <td class="px-5 py-3">
                                <span class="rounded-[14px] bg-paper px-3 py-1">{{ \App\Models\User::ACCOUNT_STATUSES[$account->status] }}</span>
                            </td>
                            <td class="px-5 py-3">
                                @if ($account->roles_exists)
                                    <span class="text-muted">Akun ber-role</span>
                                @else
                                    <a href="{{ route('admin.accounts.edit', $account) }}" aria-label="Edit {{ $account->name }}" class="inline-flex min-h-11 items-center rounded-[10px] px-2 font-bold underline decoration-control/50 underline-offset-4 hover:bg-paper">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($accounts->hasPages())
        <div class="mt-5">{{ $accounts->links() }}</div>
    @endif
@endsection
