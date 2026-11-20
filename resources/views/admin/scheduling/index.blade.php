@extends('layouts.admin')

@section('title', $label)

@section('content')
    <div data-enter class="admin-page-header">
        <h1 class="admin-title">{{ $label }}</h1>
        <a href="{{ route('admin.'.$resource.'.create') }}" class="admin-button admin-button--primary">Tambah {{ Str::lower($label) }}</a>
    </div>
    @if (session('scheduling_status'))
        <p role="status" class="mt-4 text-[#26734d]">{{ session('scheduling_status') }}</p>
    @endif

    <div class="mt-6 divide-y divide-line admin-panel">
        @forelse ($records as $record)
            <div class="flex items-start justify-between gap-4 p-4 sm:p-5">
                <div class="min-w-0 wrap-anywhere">
                    <p class="font-bold">{{ $record->name }}</p>
                    @if ($resource === 'work-shifts')
                        <p class="text-sm text-muted">{{ $record->code }} · {{ $record->attendanceTime->name }}</p>
                        <p class="text-sm text-muted">
                            {{ substr($record->attendanceTime->expected_check_in, 0, 5) }}–{{ substr($record->attendanceTime->expected_check_out, 0, 5) }}
                            @if ($record->attendanceTime->expected_check_out_day === 1) (pulang hari berikutnya) @endif
                        </p>
                    @else
                        <p class="text-sm text-muted">{{ $record->office?->name ?? 'Lintas kantor' }}</p>
                        <ul class="mt-2 flex flex-wrap gap-2 text-sm">
                            @foreach ($record->days as $day)
                                <li class="rounded-[10px] bg-paper px-2 py-1">{{ $record::WEEKDAYS[$day->weekday_number] }} · {{ $day->shift?->name ?? 'Libur' }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <p class="mt-1 text-sm font-bold text-muted">{{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                </div>
                <a href="{{ route('admin.'.$resource.'.edit', $record) }}" aria-label="Edit {{ $record->name }}" class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-[10px] px-2 font-bold underline hover:bg-paper">Edit</a>
            </div>
        @empty
            <p class="p-5 text-muted">Belum ada {{ Str::lower($label) }}.</p>
        @endforelse
    </div>
    @if ($records->hasPages())
        <div class="mt-5">{{ $records->links() }}</div>
    @endif
@endsection
