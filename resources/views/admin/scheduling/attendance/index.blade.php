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

    <ul class="mt-6 divide-y divide-line rounded-[22px] border border-line bg-white">
        @forelse ($records as $record)
            <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div>
                    <p class="font-bold">{{ $record->employee->full_name }} · {{ $record->employee->employee_code }}</p>
                    <p class="text-sm text-muted">{{ $record->work_date->locale('id')->translatedFormat('j F Y') }} · {{ $record->check_out_at ? 'Masuk dan pulang tercatat' : 'Belum catat pulang' }}</p>
                </div>
                <a href="{{ route('admin.attendance-records.show', $record) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat absensi</a>
            </li>
        @empty
            <li class="p-5 text-muted">Belum ada catatan absensi.</li>
        @endforelse
    </ul>
    <div class="mt-3">{{ $records->links() }}</div>
@endsection
