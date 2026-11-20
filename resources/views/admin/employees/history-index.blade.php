@extends('layouts.admin')

@section('title', $history->label().' pegawai')

@section('content')
    <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke data pegawai</a>
    <div data-enter class="mt-2 admin-page-header">
        <div>
            <h1 class="admin-title">@yield('title')</h1>
            <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
        </div>
        <a href="{{ route($history->routeName('create'), $employee) }}" class="admin-button admin-button--primary">Tambah {{ mb_strtolower($history->label()) }}</a>
    </div>
    @include('admin.employees.profile-nav')

    @if (session('employee_status'))
        <p role="status" class="mt-4 font-bold text-[#26734d]">{{ session('employee_status') }}</p>
    @endif

    <ul class="mt-6 divide-y divide-line admin-panel">
        @forelse ($records as $record)
            <li class="px-4 py-4 sm:px-5">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
                    <h2 class="min-w-0 font-heading text-lg font-extrabold">{{ $record->{$history->headingField()} }}</h2>
                    <a href="{{ route($history->routeName('edit'), [$employee, $record]) }}"
                        aria-label="Edit {{ $record->{$history->headingField()} }}"
                        class="admin-button admin-button--quiet underline">Edit</a>
                </div>
                <dl class="mt-2 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($history->fields() as $field => [$label, $type])
                        @if ($field !== $history->headingField() && $record->{$field} !== null)
                            <div @class(['min-w-0', 'sm:col-span-2 xl:col-span-3' => $type === 'textarea'])>
                                <dt class="text-muted">{{ $label }}</dt>
                                <dd class="whitespace-pre-wrap">{{ $type === 'date' ? $record->{$field}->locale('id')->translatedFormat('j F Y') : $record->{$field} }}</dd>
                            </div>
                        @endif
                    @endforeach
                    @if ($history === \App\Employee\EmployeeHistory::Coaching)
                        <div>
                            <dt class="text-muted">Dicatat oleh</dt>
                            <dd>{{ $record->createdBy->name }}</dd>
                        </div>
                    @endif
                </dl>
                @if ($history === \App\Employee\EmployeeHistory::Training && $record->certificate_file_path)
                    <a href="{{ route($history->routeName('certificate'), [$employee, $record]) }}"
                        class="mt-2 inline-flex min-h-11 items-center text-sm font-bold underline">Unduh sertifikat</a>
                @endif
            </li>
        @empty
            <li class="p-5 text-muted">Belum ada riwayat {{ mb_strtolower($history->label()) }}.</li>
        @endforelse
    </ul>

    @if ($records->hasPages())
        <div class="mt-5">{{ $records->links() }}</div>
    @endif
@endsection
