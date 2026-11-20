@extends('layouts.admin')

@section('title', 'Keluarga pegawai')

@section('content')
    <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke data pegawai</a>
    <div data-enter class="mt-2 admin-page-header">
        <div>
            <h1 class="admin-title">Keluarga pegawai</h1>
            <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
        </div>
        <a href="{{ route('admin.employees.family-members.create', $employee) }}" class="admin-button admin-button--primary">Tambah anggota keluarga</a>
    </div>
    @include('admin.employees.profile-nav')

    @if (session('employee_status'))
        <p role="status" class="mt-4 text-[#26734d]">{{ session('employee_status') }}</p>
    @endif

    <ul class="mt-6 divide-y divide-line admin-panel">
        @forelse ($familyMembers as $member)
            @php
                $start = $member->tax_dependent_start_date;
                $end = $member->tax_dependent_end_date;
                $status = match (true) {
                    ! $member->is_tax_dependent => 'Bukan tanggungan',
                    $start->toDateString() > $today => 'Tanggungan terjadwal',
                    $end !== null && $end->toDateString() < $today => 'Tanggungan berakhir',
                    default => 'Tanggungan aktif',
                };
            @endphp
            <li class="flex items-start justify-between gap-4 p-4 sm:p-5">
                <div class="min-w-0 wrap-anywhere">
                    <p class="font-bold">{{ $member->full_name }}</p>
                    <p class="text-sm text-muted">{{ $member->relationship }} · {{ $status }}</p>
                    @if ($start)
                        <p class="mt-1 text-sm text-muted">
                            {{ $start->locale('id')->translatedFormat('j F Y') }}
                            {{ $end ? 'sampai '.$end->locale('id')->translatedFormat('j F Y') : '· tanpa tanggal akhir' }}
                        </p>
                    @endif
                </div>
                <a href="{{ route('admin.employees.family-members.edit', [$employee, $member]) }}" aria-label="Edit data {{ $member->full_name }}"
                    class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-[10px] px-2 font-bold underline hover:bg-paper">Edit</a>
            </li>
        @empty
            <li class="p-5 text-muted">Belum ada anggota keluarga yang tercatat.</li>
        @endforelse
    </ul>
    @if ($familyMembers->hasPages())
        <div class="mt-5">{{ $familyMembers->links() }}</div>
    @endif
@endsection
