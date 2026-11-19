@extends('layouts.admin')
@section('title', 'Rincian cuti')

@section('content')
    <a href="{{ route('admin.leave-requests.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke permohonan cuti</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">Rincian cuti</h1>
    @if (session('leave_status'))
        <p role="status" class="mt-3 text-[#26734d]">{{ session('leave_status') }}</p>
    @endif
    <section class="mt-5 rounded-[22px] border border-line bg-white p-5">
        <h2 class="font-heading text-xl font-extrabold">{{ $leaveRequest->employee->full_name }} · {{ $leaveRequest->employee->employee_code }}</h2>
        <p class="mt-1 text-sm text-muted">{{ $leaveRequest->request_number }} · {{ $detail->category }}</p>
        <p class="mt-1 text-sm font-bold">{{ ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'][$leaveRequest->status] }}</p>
        @if ($detail->reason)
            <p class="mt-2 wrap-anywhere whitespace-pre-wrap">{{ $detail->reason }}</p>
        @endif
        @if ($detail->attachment_path)
            <a href="{{ route('admin.leave-requests.attachment', $leaveRequest) }}" class="inline-flex min-h-11 items-center font-bold underline">Unduh lampiran</a>
        @endif
    </section>
    <section class="mt-6">
        <h2 class="font-heading text-xl font-extrabold">Tanggal dan jatah cuti</h2>
        <ul class="mt-3 divide-y divide-line rounded-[22px] border border-line bg-white">
            @foreach ($days as $day)
                <li class="flex flex-wrap justify-between gap-2 px-4 py-3">
                    <span class="font-bold">{{ \Carbon\CarbonImmutable::parse($day->leave_date)->locale('id')->translatedFormat('j F Y') }}</span>
                    <span class="text-sm text-muted">{{ rtrim(rtrim(number_format($day->duration_days, 2, ',', ''), '0'), ',') }} hari · Jatah {{ $day->entitlement_year }}</span>
                </li>
            @endforeach
        </ul>
    </section>
@endsection
