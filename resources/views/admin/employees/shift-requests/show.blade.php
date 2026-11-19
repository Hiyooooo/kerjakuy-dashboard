@extends('layouts.admin')
@php
    $prefix = $overtime ? 'admin.overtime-requests' : 'admin.shift-swap-requests';
    $title = $overtime ? 'Rincian lembur' : 'Rincian tukar shift';
    $date = fn ($day) => \Carbon\CarbonImmutable::parse($day)->locale('id')->translatedFormat('j F Y');
    $fields = $overtime ? [
        'Tanggal mulai lembur' => $date($detail->overtime_date),
        'Jam mulai' => substr($detail->start_time, 0, 5).' WIB',
        'Jam selesai' => substr($detail->end_time, 0, 5).' WIB'.($detail->end_time < $detail->start_time ? ' (hari berikutnya)' : ''),
        'Durasi' => $detail->total_minutes.' menit',
    ] : [
        'Shift pemohon' => $date($detail->requester_work_date).' · '.$detail->original_shift.' → '.$detail->partner_shift,
        'Rekan tukar shift' => $detail->target_name.' · '.$detail->target_code,
        'Shift rekan' => $date($detail->target_work_date).' · '.$detail->partner_shift.' → '.$detail->original_shift,
    ];
@endphp
@section('title', $title)

@section('content')
    <a href="{{ route($prefix.'.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke daftar</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $title }}</h1>
    @if (session('shift_request_status'))
        <p role="status" class="mt-3 text-[#26734d]">{{ session('shift_request_status') }}</p>
    @endif
    <section class="mt-5 max-w-xl rounded-[22px] border border-line bg-white p-5">
        <h2 class="font-heading text-xl font-extrabold">{{ $record->employee->full_name }} · {{ $record->employee->employee_code }}</h2>
        <p class="mt-1 text-sm text-muted">{{ $record->request_number }}</p>
        <p class="mt-1 text-sm font-bold">{{ ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'][$record->status] }}</p>
        <dl class="mt-3 grid gap-3 sm:grid-cols-2">
            @foreach ($fields as $label => $value)
                <div>
                    <dt class="text-sm font-bold">{{ $label }}</dt>
                    <dd class="wrap-anywhere text-muted">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
        @if ($detail->reason)
            <p class="mt-3 wrap-anywhere whitespace-pre-wrap text-muted">{{ $detail->reason }}</p>
        @endif
        @if ($record->reviewed_at)
            <p class="mt-3 text-sm font-bold">{{ $record->reviewed_by_name ?? $record->reviewer?->name ?? 'Akun peninjau tidak tersedia' }} · {{ $record->reviewed_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') }} WIB</p>
            @if ($record->reviewer_note)
                <p class="mt-1 wrap-anywhere whitespace-pre-wrap text-muted">{{ $record->reviewer_note }}</p>
            @endif
        @endif
    </section>
    @if (! $overtime && $record->status === 'pending')
        <section class="mt-5 max-w-xl rounded-[22px] border border-line bg-white p-5">
            <h2 class="font-heading text-xl font-extrabold">Persetujuan tukar shift</h2>
            @if ($detail->requester_schedule_snapshot === null || $detail->target_schedule_snapshot === null)
                <p class="mt-1 text-sm text-muted">Jam efektif saat pengajuan lama tidak tercatat.</p>
                <a href="{{ route($prefix.'.create') }}" class="inline-flex min-h-11 items-center font-bold underline">Buat permohonan baru</a>
            @else
            <p class="mt-1 text-sm text-muted">Persetujuan langsung memperbarui jadwal kedua pegawai.</p>
            @foreach (['approval', 'work_date', 'work_shift_id', 'office_id', 'edit_version'] as $error)
                @error($error)<p role="alert" class="mt-2 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            @endforeach
            <form action="{{ route('admin.shift-swap-requests.approve', $record) }}" method="POST" class="mt-3 grid gap-3">
                @csrf
                <label for="reviewer_note" class="text-sm font-bold">Catatan keputusan (opsional)</label>
                <textarea id="reviewer_note" name="reviewer_note" rows="2" maxlength="2000" class="w-full rounded-[10px] border border-control px-3 py-2"
                    @error('reviewer_note') aria-invalid="true" aria-describedby="reviewer-note-error" @enderror>{{ is_string(old('reviewer_note')) ? old('reviewer_note') : '' }}</textarea>
                @error('reviewer_note')<p id="reviewer-note-error" class="text-sm text-[#b42318]">{{ $message }}</p>@enderror
                <button type="submit" class="min-h-12 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 sm:justify-self-start">Setujui tukar shift</button>
            </form>
            @endif
        </section>
    @endif
@endsection
