@extends('layouts.admin')
@php
    $prefix = $overtime ? 'admin.overtime-requests' : 'admin.shift-swap-requests';
    $title = $overtime ? 'Permohonan lembur' : 'Permohonan tukar shift';
@endphp
@section('title', $title)

@section('content')
    <div class="admin-page-header">
        <h1 data-enter class="admin-title">{{ $title }}</h1>
        <a href="{{ route($prefix.'.create') }}" class="admin-button admin-button--primary">Buat {{ Str::lower($title) }}</a>
    </div>
    <ul class="mt-6 divide-y divide-line admin-panel">
        @forelse ($requests as $record)
            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                <div>
                    <p class="font-bold">{{ $record->employee->full_name }} · {{ $record->employee->employee_code }}</p>
                    <p class="text-sm text-muted">{{ $record->request_number }} · {{ \Carbon\CarbonImmutable::parse($record->request_date)->locale('id')->translatedFormat('j F Y') }}</p>
                    <p class="text-sm font-bold">{{ ['pending' => 'Menunggu persetujuan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan'][$record->status] }}</p>
                </div>
                <a href="{{ route($prefix.'.show', $record) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat rincian<span class="sr-only"> {{ $record->request_number }}</span></a>
            </li>
        @empty
            <li class="p-5 text-muted">Belum ada {{ Str::lower($title) }}.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
