@extends('layouts.admin')
@section('title', 'Permohonan cuti')

@section('content')
    <h1 data-enter class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Permohonan cuti</h1>
    <div class="mt-4 flex flex-wrap gap-3">
        <a href="{{ route('admin.leave-requests.create') }}" class="inline-flex min-h-11 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Buat permohonan cuti</a>
        <a href="{{ route('admin.leave-requests.entitlements.create') }}" class="inline-flex min-h-11 items-center rounded-[10px] border border-control px-4 font-bold hover:bg-paper">Tambahkan jatah cuti</a>
    </div>
    <ul class="mt-6 divide-y divide-line rounded-[22px] border border-line bg-white">
        @forelse ($requests as $request)
            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-3">
                <div>
                    <p class="font-bold">{{ $request->employee->full_name }} · {{ $request->employee->employee_code }}</p>
                    <p class="text-sm text-muted">{{ $request->request_number }}</p>
                </div>
                <a href="{{ route('admin.leave-requests.show', $request) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat rincian<span class="sr-only"> {{ $request->request_number }}</span></a>
            </li>
        @empty
            <li class="p-5 text-muted">Belum ada permohonan cuti.</li>
        @endforelse
    </ul>
    <div class="mt-4">{{ $requests->links() }}</div>
@endsection
