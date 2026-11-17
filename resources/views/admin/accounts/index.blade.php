@extends('layouts.admin')

@section('title', 'Akses pengguna')

@section('content')
    <div data-enter class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="font-heading text-3xl leading-tight font-extrabold tracking-tight sm:text-[40px]">Akses pengguna</h1>
        <a href="{{ route('admin.accounts.create') }}" class="inline-flex min-h-12 items-center gap-2 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">
            <span aria-hidden="true" class="text-xl leading-none">+</span>
            Tambah akun
        </a>
    </div>

    <form method="GET" action="{{ route('admin.accounts.index') }}" role="search" class="mt-6">
        <label for="account-search" class="block text-sm font-bold">Cari nama atau email akun</label>
        <div class="mt-1 flex flex-wrap items-center gap-2">
            <input id="account-search" name="search" type="search" value="{{ $search }}" maxlength="150"
                class="min-h-12 w-full min-w-0 rounded-[10px] border border-control bg-white px-3 sm:w-72">
            <button type="submit" class="min-h-12 cursor-pointer rounded-[10px] border border-control bg-white px-4 font-bold hover:bg-paper">Cari</button>
            @if ($search !== '')
                <a href="{{ route('admin.accounts.index') }}" class="inline-flex min-h-11 items-center px-2 font-bold underline">Hapus pencarian</a>
            @endif
        </div>
    </form>
    @include('admin.accounts.role-help')

    @if (session('account_status'))
        <p role="status" class="mt-5 rounded-[10px] border border-line bg-white px-4 py-3 text-[#26734d]">{{ session('account_status') }}</p>
    @endif

    @if ($accounts->isEmpty())
        <p class="mt-6 rounded-[22px] border border-line bg-white p-6 text-muted">{{ $search === '' ? 'Belum ada akun di halaman ini.' : 'Tidak ada akun yang cocok dengan pencarian ini.' }}</p>
    @else
        <div class="mt-6 overflow-x-auto rounded-[22px] border border-line bg-white focus-visible:outline-offset-[-4px]" role="region" aria-label="Daftar akun" tabindex="0">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-paper/60">
                    <tr>
                        <th scope="col" class="px-3 py-3 sm:px-5">Akun</th>
                        <th scope="col" class="px-2 py-3 sm:px-5">Status</th>
                        <th scope="col" class="px-2 py-3 text-right sm:px-5">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($accounts as $account)
                        <tr class="focus-within:bg-paper/60 hover:bg-paper/60">
                            <th scope="row" class="px-3 py-3 font-bold sm:px-5">
                                <div class="flex items-center gap-3">
                                    <span aria-hidden="true" class="hidden size-10 shrink-0 place-items-center rounded-[12px] rounded-bl-[4px] bg-coral/20 font-heading font-extrabold sm:grid">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($account->name), 0, 1)) }}</span>
                                    <div class="min-w-0">
                                        <span class="wrap-anywhere">{{ $account->name }}</span>
                                        <span class="block font-normal wrap-anywhere text-muted">{{ $account->email }}</span>
                                        <span class="mt-1 block text-xs font-bold text-muted">{{ $account->role->name }}</span>
                                        <span class="block text-xs font-normal wrap-anywhere text-muted">{{ $account->employee ? $account->employee->full_name.' ('.$account->employee->employee_code.')' : 'Tanpa pegawai' }}</span>
                                    </div>
                                </div>
                            </th>
                            <td class="px-2 py-3 sm:px-5">
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-[14px] px-2 py-1 text-xs font-bold whitespace-nowrap',
                                    'bg-[#e9f5ee] text-[#26734d]' => $account->status === 'active',
                                    'bg-paper text-muted' => $account->status !== 'active',
                                ])>
                                    <span aria-hidden="true" @class(['size-2 rounded-full border border-current', 'bg-current' => $account->status === 'active'])></span>
                                    {{ \App\Access\Models\User::ACCOUNT_STATUSES[$account->status] }}
                                </span>
                            </td>
                            <td class="px-2 py-3 text-right sm:px-5">
                                @can('admin.accounts.change', $account)
                                    <a href="{{ route('admin.accounts.edit', $account) }}" aria-label="Edit {{ $account->name }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[10px] px-2 font-bold underline decoration-control/50 underline-offset-4 hover:bg-coral/15">Edit</a>
                                @else
                                    <span class="text-muted">{{ auth()->user()->is($account) ? 'Akun Anda' : ($account->isSuperAdmin() ? 'Akun Super Admin' : 'Dilindungi') }}</span>
                                @endcan
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
