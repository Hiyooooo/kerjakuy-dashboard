@extends('layouts.admin')

@php
    $title = $inactive ? 'Pegawai nonaktif' : 'Data pegawai';
    $listingUrl = route($inactive ? 'admin.employees.inactive' : 'admin.employees.index');
    $searchParams = $search === '' ? [] : ['search' => $search];
@endphp

@section('title', $title)

@section('content')
    <div data-enter class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $title }}</h1>
        @unless ($inactive)
            <a href="{{ route('admin.employees.create') }}" class="inline-flex min-h-12 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">Tambah pegawai</a>
        @endunless
    </div>

    <nav aria-label="Daftar pegawai" class="mt-3 flex flex-wrap gap-x-2 gap-y-1 border-b border-line">
        @foreach (['admin.employees.index' => 'Semua pegawai', 'admin.employees.inactive' => 'Nonaktif & mengundurkan diri'] as $routeName => $label)
            <a href="{{ route($routeName, $searchParams) }}"
                @if (request()->routeIs($routeName)) aria-current="page" @endif
                class="inline-flex min-h-11 items-center rounded-t-[10px] border-b-[3px] border-transparent px-2 py-2 text-sm font-semibold text-muted hover:bg-white hover:text-ink aria-[current=page]:border-coral aria-[current=page]:font-extrabold aria-[current=page]:text-ink motion-safe:transition-colors sm:px-3">{{ $label }}</a>
        @endforeach
    </nav>
    @if ($inactive)
        <p class="mt-1 text-sm text-muted">Menampilkan pegawai dengan status Nonaktif atau Mengundurkan diri pada periode yang berlaku hari ini.</p>
    @endif

    <form method="GET" action="{{ $listingUrl }}" role="search" class="mt-6">
        <label for="employee-search" class="block text-sm font-bold">Cari nama atau kode pegawai</label>
        <div class="mt-1 flex flex-wrap items-center gap-2">
            <input id="employee-search" name="search" type="search" value="{{ $search }}" maxlength="150"
                class="min-h-12 w-full min-w-0 rounded-[10px] border border-control bg-white px-3 sm:w-72">
            <button type="submit" class="min-h-12 cursor-pointer rounded-[10px] border border-control bg-white px-4 font-bold hover:bg-paper">Cari</button>
            @if ($search !== '')
                <a href="{{ $listingUrl }}" class="inline-flex min-h-11 items-center px-2 font-bold underline decoration-control/50 underline-offset-4">Hapus pencarian</a>
            @endif
        </div>
    </form>

    @if (session('employee_status'))
        <p role="status" class="mt-5 rounded-[10px] border border-line bg-white px-4 py-3 text-[#26734d]">{{ session('employee_status') }}</p>
    @endif

    @if ($employees->isEmpty())
        <p class="mt-6 rounded-[22px] border border-line bg-white p-6 text-muted">
            @if ($inactive)
                {{ $search === '' ? 'Tidak ada pegawai nonaktif atau mengundurkan diri hari ini.' : 'Tidak ada pegawai nonaktif atau mengundurkan diri yang cocok dengan pencarian ini.' }}
            @else
                {{ $search === '' ? 'Belum ada pegawai di halaman ini.' : 'Tidak ada pegawai yang cocok dengan pencarian ini.' }}
            @endif
        </p>
    @else
        <div class="mt-6 overflow-hidden rounded-[22px] border border-line bg-white">
            <table class="w-full table-fixed text-left text-sm">
                <thead class="border-b border-line bg-paper">
                    <tr>
                        <th scope="col" class="px-3 py-3 sm:px-5">Nama dan kode pegawai</th>
                        <th scope="col" class="w-20 px-2 py-3 text-right sm:w-24 sm:px-5">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($employees as $employee)
                        @php
                            $period = $employee->periods->first();
                            $assignment = $employee->assignments->first();
                        @endphp
                        <tr class="focus-within:bg-paper/60 hover:bg-paper/60">
                            <th scope="row" class="px-3 py-3 font-bold wrap-anywhere sm:px-5">
                                {{ $employee->full_name }}
                                <span class="mt-0.5 block font-normal text-muted">{{ $employee->employee_code }}</span>
                                <span class="mt-2 block text-sm font-normal text-muted">
                                    {{ $period ? $period::STATUSES[$period->status].' sejak '.$period->effective_start_date->locale('id')->translatedFormat('j F Y') : 'Belum ada periode berlaku' }}
                                </span>
                                <span class="mt-0.5 block text-sm font-normal text-muted">
                                    {{ $assignment ? $assignment->position->name.' · '.$assignment->office->name : 'Tidak ada penempatan berlaku' }}
                                </span>
                            </th>
                            <td class="px-2 py-3 text-right sm:px-5">
                                <a href="{{ route('admin.employees.edit', $employee) }}" aria-label="Edit {{ $employee->full_name }} ({{ $employee->employee_code }})" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[10px] px-2 font-bold underline decoration-control/50 underline-offset-4 hover:bg-paper">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($employees->hasPages())
        <div class="mt-5">{{ $employees->links() }}</div>
    @endif
@endsection
