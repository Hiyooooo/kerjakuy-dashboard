@extends('layouts.admin')

@section('title', 'Riwayat kepegawaian')

@section('content')
    <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke data pegawai</a>
    <div data-enter class="mt-2 admin-page-header">
        <div>
            <h1 class="admin-title">Riwayat kepegawaian</h1>
            <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
        </div>
        <a href="{{ route('admin.employees.periods.create', $employee) }}" class="admin-button admin-button--primary">Tambah periode</a>
    </div>
    @include('admin.employees.profile-nav')
    @if (session('employee_status'))
        <p role="status" class="mt-4 text-[#26734d]">{{ session('employee_status') }}</p>
    @endif

    <div class="mt-6 divide-y divide-line admin-panel">
        @forelse ($periods as $period)
            @php
                $start = $period->effective_start_date;
                $end = $period->effective_end_date;
                $current = $start->toDateString() <= $today && (! $end || $end->toDateString() >= $today);
                $timing = match (true) {
                    $start->toDateString() > $today => 'Terjadwal',
                    $end && $end->toDateString() < $today => 'Berakhir',
                    default => 'Berlaku sekarang',
                };
            @endphp
            <div class="flex items-start justify-between gap-4 p-4 sm:p-5">
                <div class="min-w-0 wrap-anywhere">
                    <p class="font-bold">{{ $period::TYPES[$period->employment_type] }} · {{ $period::STATUSES[$period->status] }}</p>
                    <p class="text-sm text-muted">Berlaku {{ $start->locale('id')->translatedFormat('j F Y') }} {{ $end ? 'sampai '.$end->locale('id')->translatedFormat('j F Y') : 'tanpa batas akhir' }}</p>
                    <p class="mt-2 flex flex-wrap items-center gap-2 text-sm text-muted">
                        <span @class(['rounded-[14px] px-2 py-1 text-xs font-bold', 'bg-[#e9f5ee] text-[#26734d]' => $current, 'bg-paper' => ! $current])>{{ $timing }}</span>
                    </p>
                    @if ($period->reason)
                        <p class="mt-1 text-sm">{{ $period->reason }}</p>
                    @endif
                </div>
                <a href="{{ route('admin.employees.periods.edit', [$employee, $period]) }}" aria-label="Edit periode mulai {{ $start->toDateString() }}" class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-[10px] px-2 font-bold underline hover:bg-paper">Edit</a>
            </div>
        @empty
            <p class="p-5 text-muted">Belum ada periode kepegawaian.</p>
        @endforelse
    </div>
    @if ($periods->hasPages())
        <div class="mt-5">{{ $periods->links() }}</div>
    @endif
@endsection
