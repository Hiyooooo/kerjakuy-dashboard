@extends('layouts.admin')
@section('title', 'Permohonan cuti')

@section('content')
    <header data-enter class="admin-page-header">
        <h1 class="admin-title">Permohonan cuti</h1>
        <div class="admin-header-actions flex flex-wrap gap-2">
            <a href="{{ route('admin.leave-requests.balances') }}" class="admin-button admin-button--secondary">Lihat saldo cuti</a>
            <a href="{{ route('admin.leave-requests.entitlements.create') }}" class="admin-button admin-button--secondary">Tambahkan jatah cuti</a>
            <a href="{{ route('admin.leave-requests.create') }}" class="admin-button admin-button--primary">Buat permohonan cuti</a>
        </div>
    </header>
    <form method="GET" action="{{ route('admin.leave-requests.index') }}" aria-label="Filter permohonan" class="admin-toolbar flex flex-wrap items-end gap-3">
        <label class="grid w-full max-w-xs gap-1 text-sm font-bold">Nama, kode pegawai, atau nomor permohonan
            <input type="search" name="search" maxlength="150" value="{{ $filters['search'] ?? '' }}" class="min-h-11 min-w-0 rounded-[10px] border border-control bg-white px-3">
        </label>
        <label class="grid gap-1 text-sm font-bold">Status
            <select name="status" class="min-h-11 rounded-[10px] border border-control bg-white px-3">
                <option value="">Semua status</option>
                @foreach (['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="admin-button admin-button--secondary">Terapkan filter</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.leave-requests.index') }}" class="inline-flex min-h-11 items-center px-2 font-bold underline">Reset filter</a>
        @endif
    </form>

    <ul class="mt-6 divide-y divide-line admin-panel">
        @forelse ($requests as $request)
            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                <div>
                    <p class="font-bold">{{ $request->employee->full_name }} · {{ $request->employee->employee_code }}</p>
                    <p class="text-sm text-muted">{{ $request->request_number }} · {{ $request->category }}</p>
                    <p class="mt-1 text-sm">
                        {{ rtrim(rtrim(number_format((float) $request->total_days, 2, ',', ''), '0'), ',') }} hari
                        @if ($request->leave_dates)
                            · {{ \Carbon\CarbonImmutable::parse($request->leave_dates[0])->locale('id')->translatedFormat('j M Y') }}
                            @if (count($request->leave_dates) > 1)
                                + {{ count($request->leave_dates) - 1 }} tanggal lain
                            @endif
                        @endif
                    </p>
                    <span @class(['admin-status mt-2', 'bg-amber/20' => $request->status === 'pending', 'bg-green-50 text-[#26734d]' => $request->status === 'approved', 'bg-paper' => in_array($request->status, ['rejected', 'cancelled'], true)])>
                        {{ ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'][$request->status] }}
                    </span>
                </div>
                <a href="{{ route('admin.leave-requests.show', $request) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat rincian<span class="sr-only"> {{ $request->request_number }}</span></a>
            </li>
        @empty
            <li class="p-5 text-muted">{{ array_filter($filters) ? 'Tidak ada permohonan cuti yang cocok dengan filter.' : 'Belum ada permohonan cuti.' }}</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
