@extends('layouts.admin')

@section('title', 'Catatan absensi')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 data-enter class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Catatan absensi</h1>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('admin.face-verification-logs.index') }}" class="inline-flex min-h-11 items-center px-2 font-bold underline">Riwayat verifikasi wajah</a>
            <a href="{{ route('admin.attendance-records.create') }}" class="inline-flex min-h-11 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Catat absensi masuk</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.attendance-records.index') }}" aria-label="Filter catatan" class="mt-6 flex flex-wrap items-end gap-3">
        <label class="grid w-full max-w-xs gap-1 text-sm font-bold">Nama atau kode pegawai
            <input type="search" name="search" maxlength="150" value="{{ $filters['search'] ?? '' }}" class="min-h-11 min-w-0 rounded-[10px] border border-control bg-white px-3">
        </label>
        @foreach (['date_from' => 'Tanggal kerja mulai', 'date_to' => 'Tanggal kerja sampai'] as $field => $label)
            @php($date = old($field, $filters[$field] ?? ''))
            <label class="grid gap-1 text-sm font-bold">{{ $label }}
                <input type="date" name="{{ $field }}" min="0001-01-01" max="9999-12-31" value="{{ is_string($date) ? $date : '' }}"
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                    class="min-h-11 min-w-0 rounded-[10px] border border-control bg-white px-3">
                @error($field)
                    <span id="{{ $field }}-error" class="max-w-48 font-normal text-[#b42318]">{{ $message }}</span>
                @enderror
            </label>
        @endforeach
        <label class="flex min-h-11 items-center gap-2 text-sm font-bold">
            <input type="checkbox" name="missing_checkout" value="1" @checked($filters['missing_checkout'] ?? false) class="size-4 accent-coral">
            Belum catat pulang
        </label>
        <button class="min-h-11 rounded-[10px] border border-control bg-white px-4 font-bold hover:bg-paper">Terapkan filter</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.attendance-records.index') }}" class="inline-flex min-h-11 items-center px-2 font-bold underline">Reset filter</a>
        @endif
    </form>

    <ul class="mt-6 divide-y divide-line rounded-[22px] border border-line bg-white">
        @forelse ($records as $record)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <p class="font-bold">{{ $record->employee->full_name }} · {{ $record->employee->employee_code }}</p>
                    <p class="text-sm text-muted">{{ $record->work_date->locale('id')->translatedFormat('j F Y') }}</p>
                    <p class="mt-1 text-sm tabular-nums">
                        Masuk {{ $record->check_in_at?->setTimezone('Asia/Jakarta')->format('H:i') ?? '—' }} ·
                        Pulang {{ $record->check_out_at?->setTimezone('Asia/Jakarta')->format('H:i') ?? '—' }} WIB
                    </p>
                    @if ($record->check_in_at && ! $record->check_out_at)
                        <span class="mt-1 inline-block rounded-full bg-amber/20 px-2 py-1 text-xs font-bold">Belum catat pulang</span>
                    @endif
                    @if ($record->late_minutes > 0)
                        <span class="mt-1 inline-block rounded-full bg-coral/15 px-2 py-1 text-xs font-bold">Terlambat {{ $record->late_minutes }} menit</span>
                    @endif
                </div>
                <a href="{{ route('admin.attendance-records.show', $record) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat absensi</a>
            </li>
        @empty
            <li class="p-5 text-muted">{{ array_filter($filters) ? 'Tidak ada catatan absensi yang cocok dengan filter.' : 'Belum ada catatan absensi.' }}</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $records->links() }}</div>
@endsection
