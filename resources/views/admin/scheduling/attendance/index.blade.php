@extends('layouts.admin')

@section('title', 'Catatan absensi')

@section('content')
    <div class="admin-page-header">
        <h1 data-enter class="admin-title">Catatan absensi</h1>
        <div class="admin-header-actions flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.face-verification-logs.index') }}" class="admin-button admin-button--secondary">Riwayat verifikasi wajah</a>
            <a href="{{ route('admin.attendance-records.create') }}" class="admin-button admin-button--primary">Catat absensi masuk</a>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.attendance-records.index') }}" aria-label="Filter catatan" class="admin-toolbar flex flex-wrap items-end gap-3">
        <label class="grid w-full gap-1 text-sm font-bold sm:max-w-xs">Nama atau kode pegawai
            <input type="search" name="search" maxlength="150" value="{{ $filters['search'] ?? '' }}" class="min-h-11 min-w-0 rounded-[10px] border border-control bg-white px-3">
        </label>
        @foreach (['date_from' => 'Tanggal kerja mulai', 'date_to' => 'Tanggal kerja sampai'] as $field => $label)
            @php($date = old($field, $filters[$field] ?? ''))
            <label class="grid w-full gap-1 text-sm font-bold sm:w-auto">{{ $label }}
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
        <button class="admin-button admin-button--secondary">Terapkan filter</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.attendance-records.index') }}" class="inline-flex min-h-11 items-center px-2 font-bold underline">Reset filter</a>
        @endif
    </form>

    <ul class="mt-6 divide-y divide-line admin-panel">
        @forelse ($records as $record)
            <li class="grid min-w-0 items-center gap-3 px-4 py-4 lg:grid-cols-[minmax(0,2fr)_minmax(0,1.4fr)_auto] lg:px-5">
                <div class="min-w-0">
                    <p class="font-bold">{{ $record->employee->full_name }}</p>
                    <p class="text-sm text-muted">{{ $record->employee->employee_code }} · {{ $record->work_date->locale('id')->translatedFormat('j F Y') }}</p>
                </div>
                <div class="min-w-0">
                    <dl class="grid grid-cols-2 gap-3 text-sm tabular-nums">
                        <div>
                            <dt class="text-muted">Masuk</dt>
                            <dd class="font-bold">{{ $record->check_in_at ? $record->check_in_at->setTimezone('Asia/Jakarta')->format('H:i').' WIB' : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted">Pulang</dt>
                            <dd class="font-bold">{{ $record->check_out_at ? $record->check_out_at->setTimezone('Asia/Jakarta')->format('H:i').' WIB' : '—' }}</dd>
                        </div>
                    </dl>
                    @if ($record->check_in_at && ! $record->check_out_at)
                        <span class="admin-status mt-2 bg-amber/20">Belum catat pulang</span>
                    @endif
                    @if ($record->late_minutes > 0)
                        <span class="admin-status mt-2 bg-coral/15">Terlambat {{ $record->late_minutes }} menit</span>
                    @endif
                </div>
                <a href="{{ route('admin.attendance-records.show', $record) }}" class="admin-button admin-button--quiet justify-self-start underline lg:justify-self-end">Lihat absensi</a>
            </li>
        @empty
            <li class="p-5 text-muted">{{ array_filter($filters) ? 'Tidak ada catatan absensi yang cocok dengan filter.' : 'Belum ada catatan absensi.' }}</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $records->links() }}</div>
@endsection
