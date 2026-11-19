@extends('layouts.admin')
@section('title', 'Persetujuan permohonan')

@section('content')
    <h1 data-enter class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Persetujuan permohonan</h1>

    @if (session('approval_status'))
        <p role="status" class="mt-3 text-[#26734d]">{{ session('approval_status') }}</p>
    @endif
    @if ($errors->any())
        <ul role="alert" class="mt-3 space-y-1 text-sm text-[#b42318]">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('admin.request-approvals.index') }}" method="GET" class="mt-5 flex flex-wrap items-end gap-3">
        <label class="grid gap-1 text-sm font-bold">Jenis permohonan
            <select name="type" class="min-h-11 rounded-[10px] border border-control bg-white px-3">
                <option value="">Semua jenis</option>
                @foreach ($types as $key => [$label])
                    <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="grid gap-1 text-sm font-bold">Status
            <select name="status" class="min-h-11 rounded-[10px] border border-control bg-white px-3">
                <option value="all" @selected($status === 'all')>Semua status</option>
                @foreach ($statuses as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="min-h-11 rounded-[10px] border border-control bg-white px-4 font-bold hover:bg-paper">Terapkan filter</button>
    </form>

    <ul class="mt-5 divide-y divide-line rounded-[22px] border border-line bg-white">
        @forelse ($records as $record)
            @php
                [$label, $detailRoute] = $types[$record->request_type];
            @endphp
            <li class="px-4 py-4 sm:px-5">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div class="min-w-0 wrap-anywhere">
                        <h2 class="font-heading text-lg font-extrabold">{{ $record->employee->full_name }} · {{ $label }}</h2>
                        <p class="text-sm text-muted">{{ $record->employee->employee_code }} · {{ $record->request_number }}</p>
                        <p class="text-sm text-muted">Diajukan {{ $record->submitted_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') }} WIB</p>
                    </div>
                    @if ($record->has_detail)
                        <a href="{{ route($detailRoute, $record) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat rincian<span class="sr-only"> {{ $record->request_number }}</span></a>
                    @else
                        <p class="text-sm text-muted">Rincian belum tersedia.</p>
                    @endif
                </div>
                <p class="mt-2 text-sm font-bold">{{ $statuses[$record->status] }}</p>
                @if ($record->status === 'pending')
                    <details class="mt-2">
                        <summary class="min-h-11 w-fit cursor-pointer py-2 text-sm font-bold underline">Proses permohonan</summary>
                        <form method="POST" action="{{ route('admin.request-approvals.decide', $record) }}" class="grid max-w-lg gap-3">
                            @csrf
                            <label for="reviewer-note-{{ $record->id }}" class="text-sm font-bold">Catatan keputusan (opsional)</label>
                            <textarea id="reviewer-note-{{ $record->id }}" name="reviewer_note" maxlength="2000" rows="2" class="rounded-[10px] border border-control px-3 py-2"></textarea>
                            <div class="flex flex-wrap gap-2">
                                @if ($record->has_detail)
                                    <button name="decision" value="approved" class="min-h-11 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Setujui permohonan</button>
                                @endif
                                <button name="decision" value="rejected" class="min-h-11 rounded-[10px] border border-control px-4 font-bold hover:bg-paper">Tolak permohonan</button>
                                <button name="decision" value="cancelled" class="min-h-11 rounded-[10px] border border-control px-4 font-bold hover:bg-paper">Batalkan permohonan</button>
                            </div>
                        </form>
                    </details>
                @elseif ($record->reviewed_at)
                    <p class="text-sm text-muted">{{ $record->reviewed_by_name ?? $record->reviewer?->name ?? 'Akun peninjau tidak tersedia' }} · {{ $record->reviewed_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') }} WIB</p>
                    @if ($record->reviewer_note)
                        <p class="mt-1 wrap-anywhere whitespace-pre-wrap text-sm text-muted">{{ $record->reviewer_note }}</p>
                    @endif
                @endif
            </li>
        @empty
            <li class="p-5 text-muted">Tidak ada permohonan untuk filter ini.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $records->links() }}</div>
@endsection
