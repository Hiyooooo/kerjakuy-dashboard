@extends('layouts.admin')
@section('title', 'Rincian permohonan terlambat')

@section('content')
    <div class="admin-form-column">
        <a href="{{ route('admin.late-requests.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke permohonan terlambat</a>
        <h1 data-enter class="mt-2 admin-title">Rincian permohonan terlambat</h1>
        @if (session('late_status'))
            <p role="status" class="mt-3 text-[#26734d]">{{ session('late_status') }}</p>
        @endif
        <section class="mt-5 max-w-xl admin-panel p-5">
            <h2 class="font-heading text-xl font-extrabold">{{ $lateRequest->employee->full_name }} · {{ $lateRequest->employee->employee_code }}</h2>
            <p class="mt-1 text-sm text-muted">{{ $lateRequest->request_number }}</p>
            <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'Status' => ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'][$lateRequest->status],
                    'Tanggal kerja' => \Carbon\CarbonImmutable::parse($detail->late_date)->locale('id')->translatedFormat('j F Y'),
                    'Jadwal masuk' => $detail->expected_check_in ? substr($detail->expected_check_in, 0, 5).' WIB' : 'Belum tersedia',
                    'Jam masuk aktual' => $detail->actual_check_in ? substr($detail->actual_check_in, 0, 5).' WIB' : 'Belum tersedia',
                    'Alasan' => $detail->reason,
                ] as $label => $value)
                    <div class="min-w-0">
                        <dt class="text-sm font-bold">{{ $label }}</dt>
                        <dd class="wrap-anywhere whitespace-pre-wrap text-muted">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-3 flex flex-wrap gap-3">
                @if ($detail->attendance_record_id)
                    <a href="{{ route('admin.attendance-records.show', $detail->attendance_record_id) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat catatan absensi</a>
                @else
                    <p class="text-sm text-muted">Belum tertaut ke catatan absensi.</p>
                @endif
                @if ($detail->attachment_path)
                    <a href="{{ route('admin.late-requests.attachment', $lateRequest) }}" class="inline-flex min-h-11 items-center font-bold underline">Unduh lampiran</a>
                @endif
            </div>
        </section>
    </div>
@endsection
