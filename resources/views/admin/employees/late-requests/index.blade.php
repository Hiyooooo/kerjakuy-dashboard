@extends('layouts.admin')
@section('title', 'Permohonan terlambat')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 data-enter class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Permohonan terlambat</h1>
        <a href="{{ route('admin.late-requests.create') }}" class="inline-flex min-h-11 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Buat permohonan terlambat</a>
    </div>
    <ul class="mt-6 divide-y divide-line rounded-[22px] border border-line bg-white">
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
