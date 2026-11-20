@extends('layouts.admin')

@section('title', 'Jadwal kerja pegawai')

@section('content')
    <a href="{{ route('admin.employees.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke data pegawai</a>
    <h1 data-enter class="mt-2 admin-title">Jadwal kerja pegawai</h1>
    <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
    @include('admin.employees.profile-nav')

    @if (session('scheduling_status'))
        <p role="status" class="mt-4 text-[#26734d]">{{ session('scheduling_status') }}</p>
    @endif

    <section class="mt-6 admin-panel p-5">
        <h2 class="font-heading text-xl font-extrabold">Jadwal pada tanggal tertentu</h2>
        <form method="GET" action="{{ route('admin.employees.schedules.index', $employee) }}" class="mt-3 flex flex-wrap items-end gap-2">
            <div>
                <label for="schedule-date" class="block text-sm font-bold">Tanggal kerja</label>
                <input id="schedule-date" name="date" type="date" value="{{ $date }}" required class="mt-1 min-h-12 rounded-[10px] border border-control px-3">
            </div>
            <button type="submit" class="min-h-12 rounded-[10px] border border-control px-4 font-bold hover:bg-paper">Lihat jadwal</button>
        </form>
        <p class="mt-4 font-bold">{{ $effective['label'] }}</p>
        @if ($shift = $effective['shift'])
            <p class="mt-1 text-muted">{{ $shift->name }} · {{ substr($shift->attendanceTime->expected_check_in, 0, 5) }}–{{ substr($shift->attendanceTime->expected_check_out, 0, 5) }}@if ($shift->attendanceTime->expected_check_out_day === 1) (pulang hari berikutnya) @endif</p>
        @endif
    </section>

    <section class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-heading text-xl font-extrabold">Jadwal default</h2>
            <a href="{{ route('admin.employees.schedule-assignments.create', $employee) }}" class="admin-button admin-button--primary">Tambah jadwal default</a>
        </div>
        <div class="mt-3 divide-y divide-line admin-panel">
            @forelse ($assignments as $assignment)
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <div class="min-w-0 wrap-anywhere">
                        <p class="font-bold">{{ $assignment->template->name }}</p>
                        <p class="text-sm text-muted">{{ $assignment->effective_start_date->locale('id')->translatedFormat('j F Y') }} {{ $assignment->effective_end_date ? 'sampai '.$assignment->effective_end_date->locale('id')->translatedFormat('j F Y') : 'tanpa tanggal akhir' }}</p>
                    </div>
                    <a href="{{ route('admin.employees.schedule-assignments.edit', [$employee, $assignment]) }}" aria-label="Edit jadwal default mulai {{ $assignment->effective_start_date->toDateString() }}" class="inline-flex min-h-11 min-w-11 items-center justify-center font-bold underline">Edit</a>
                </div>
            @empty
                <p class="p-4 text-muted">Belum ada jadwal default.</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $assignments->links() }}</div>
    </section>

    <section class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-heading text-xl font-extrabold">Shift khusus harian</h2>
            <a href="{{ route('admin.employees.shift-overrides.create', [$employee, 'date' => $date]) }}" class="admin-button admin-button--primary">Tambah shift khusus</a>
        </div>
        <p class="mt-1 text-sm text-muted">Shift khusus terjadwal menggantikan jadwal default pada tanggal yang dipilih.</p>
        <div class="mt-3 divide-y divide-line admin-panel">
            @forelse ($overrides as $override)
                <div class="flex items-center justify-between gap-4 px-4 py-3">
                    <div class="min-w-0 wrap-anywhere">
                        <p class="font-bold">{{ $override->shift->name }} · {{ $override->work_date->locale('id')->translatedFormat('j F Y') }}</p>
                        <p class="text-sm text-muted">{{ $override->status === 'scheduled' ? 'Terjadwal' : 'Dibatalkan' }}@if ($override->office) · {{ $override->office->name }} @endif</p>
                        @if ($override->reason)<p class="text-sm text-muted">{{ $override->reason }}</p>@endif
                    </div>
                    <a href="{{ route('admin.employees.shift-overrides.edit', [$employee, $override]) }}" aria-label="Edit shift khusus {{ $override->work_date->toDateString() }}" class="inline-flex min-h-11 min-w-11 items-center justify-center font-bold underline">Edit</a>
                </div>
            @empty
                <p class="p-4 text-muted">Belum ada shift khusus.</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $overrides->links() }}</div>
    </section>
@endsection
