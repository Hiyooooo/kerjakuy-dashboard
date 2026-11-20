@extends('layouts.admin')
@section('title', 'Permohonan terlambat')

@section('content')
    <div class="admin-page-header">
        <h1 data-enter class="admin-title">Permohonan terlambat</h1>
        <a href="{{ route('admin.late-requests.create') }}" class="admin-button admin-button--primary">Buat permohonan terlambat</a>
    </div>
    <ul class="mt-6 divide-y divide-line admin-panel">
        @forelse ($requests as $request)
            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                <div>
                    <p class="font-bold">{{ $request->employee->full_name }} · {{ $request->employee->employee_code }}</p>
                    <p class="text-sm text-muted">{{ $request->request_number }} · {{ \Carbon\CarbonImmutable::parse($request->late_date)->locale('id')->translatedFormat('j F Y') }}</p>
                </div>
                <a href="{{ route('admin.late-requests.show', $request) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat rincian<span class="sr-only"> {{ $request->request_number }}</span></a>
            </li>
        @empty
            <li class="p-5 text-muted">Belum ada permohonan terlambat.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
