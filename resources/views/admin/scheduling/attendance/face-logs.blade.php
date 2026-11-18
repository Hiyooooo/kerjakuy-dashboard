@extends('layouts.admin')

@section('title', 'Riwayat verifikasi wajah')

@section('content')
    <a href="{{ route('admin.attendance-records.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke catatan absensi</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">Riwayat verifikasi wajah</h1>

    <form action="{{ route('admin.face-verification-logs.index') }}" method="GET" class="mt-5 flex flex-wrap items-end gap-2">
        <div>
            <label for="status" class="block text-sm font-bold">Hasil verifikasi</label>
            <select id="status" name="status" class="mt-1 min-h-11 rounded-[10px] border border-control bg-white px-3">
                <option value="">Semua hasil</option>
                <option value="passed" @selected($status === 'passed')>Lolos</option>
                <option value="failed" @selected($status === 'failed')>Gagal</option>
            </select>
        </div>
        <button class="min-h-11 rounded-[10px] border border-control px-4 font-bold hover:bg-paper">Terapkan filter</button>
    </form>

    <ul class="mt-5 divide-y divide-line rounded-[22px] border border-line bg-white">
        @forelse ($logs as $log)
            <li class="flex flex-wrap items-start justify-between gap-3 px-4 py-3">
                <div class="min-w-0 wrap-anywhere">
                    <p class="font-bold">{{ $log->employee->full_name }} · {{ $log->employee->employee_code }}</p>
                    <p class="text-sm text-muted">{{ $log->verification_type === 'check_in' ? 'Masuk' : 'Pulang' }} · {{ $log->captured_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i:s') }} WIB</p>
                    <p class="mt-1 text-sm font-bold">{{ $log->status === 'passed' ? 'Lolos' : 'Gagal' }}</p>
                    @if ($log->match_score !== null || $log->minimum_match_score !== null)
                        <p class="text-sm text-muted">Skor {{ $log->match_score ?? '—' }} · Minimum {{ $log->minimum_match_score ?? '—' }}</p>
                    @endif
                    @if ($log->failure_reason)
                        <p class="text-sm text-muted">{{ $log->failure_reason }}</p>
                    @endif
                    @unless ($log->attendance_record_id)
                        <p class="text-sm text-muted">Belum terhubung ke catatan absensi.</p>
                    @endunless
                </div>
                <div class="flex flex-wrap gap-x-3">
                    @if ($log->attendance_record_id)
                        <a href="{{ route('admin.attendance-records.show', $log->attendance_record_id) }}" class="inline-flex min-h-11 items-center text-sm font-bold underline">Lihat absensi</a>
                    @endif
                    @if ($log->captured_photo_path && auth()->user()->can('admin.attendance.photos.view'))
                        <a href="{{ route('admin.face-verification-logs.photo', $log) }}" class="inline-flex min-h-11 items-center text-sm font-bold underline">Lihat foto verifikasi</a>
                    @endif
                </div>
            </li>
        @empty
            <li class="p-5 text-muted">{{ $status ? 'Tidak ada percobaan dengan hasil ini.' : 'Belum ada percobaan verifikasi wajah.' }}</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $logs->links() }}</div>
@endsection
