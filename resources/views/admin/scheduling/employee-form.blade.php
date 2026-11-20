@extends('layouts.admin')

@php
    $editing = $record->exists;
    $resource = $defaultSchedule ? 'schedule-assignments' : 'shift-overrides';
    $prefix = 'admin.employees.'.$resource;
    $parameters = $editing ? [$employee, $record] : [$employee];
    $formUrl = route($prefix.($editing ? '.edit' : '.create'), $parameters);
    $token = request()->query('form');
    $restore = is_string($token) && old('_scheduling_token') === $token && old('_scheduling_form') === $formUrl;
    $errors = $restore ? $errors : new \Illuminate\Support\ViewErrorBag;
    $formValue = function (string $field, mixed $default) use ($restore): mixed {
        $value = $restore ? old($field, $default) : $default;
        return is_scalar($value) ? $value : '';
    };
    $masterField = $defaultSchedule ? 'work_schedule_template_id' : 'work_shift_id';
    $masterLabel = $defaultSchedule ? 'Template jadwal' : 'Shift khusus';
    $title = ($editing ? 'Edit ' : 'Tambah ').($defaultSchedule ? 'jadwal default' : 'shift khusus');
    $back = route('admin.employees.schedules.index', $employee);
    $dateFields = $defaultSchedule
        ? ['effective_start_date' => 'Tanggal mulai berlaku', 'effective_end_date' => 'Tanggal akhir berlaku (opsional)']
        : ['work_date' => 'Tanggal kerja'];
@endphp

@section('title', $title)

@section('content')
    <div class="admin-form-column">
        <a href="{{ $back }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke jadwal kerja</a>
        <h1 data-enter class="mt-2 admin-title">{{ $title }}</h1>
        <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
        @include('admin.employees.profile-nav')

        <form method="POST" action="{{ route($prefix.($editing ? '.update' : '.store'), $parameters) }}" class="mt-6 grid max-w-xl gap-5 admin-panel admin-form">
            @csrf
            @if ($editing)
                @method('PUT')
                <input name="edit_version" type="hidden" value="{{ $formValue('edit_version', $restore ? null : $record->editVersion()) }}">
                @error('edit_version')
                    <p role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>
                    <a href="{{ $formUrl }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                @enderror
            @endif

            <div>
                <label for="{{ $masterField }}" class="block text-sm font-bold">{{ $masterLabel }}</label>
                <select id="{{ $masterField }}" name="{{ $masterField }}" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                    aria-describedby="{{ $defaultSchedule ? 'schedule-template-hint' : '' }} @error($masterField) master-error @enderror"
                    @error($masterField) aria-invalid="true" @enderror>
                    <option value="">Pilih {{ Str::lower($masterLabel) }}</option>
                    @foreach ($options as $option)
                        @php
                            $active = $option->is_active && ($defaultSchedule || $option->attendanceTime->is_active);
                        @endphp
                        <option value="{{ $option->id }}" @selected((string) $formValue($masterField, $record->{$masterField}) === (string) $option->id)>{{ $option->name }} · {{ $defaultSchedule ? ($option->office?->name ?? 'Lintas kantor') : $option->code }}{{ $active ? '' : ' · Nonaktif' }}</option>
                    @endforeach
                </select>
                @if ($defaultSchedule)
                    <p id="schedule-template-hint" class="mt-1 text-sm text-muted">Template kantor berlaku jika pegawai ditempatkan di kantor itu sepanjang periode jadwal.</p>
                @endif
                @error($masterField)<p id="master-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            </div>

            @foreach ($dateFields as $field => $label)
                @php
                    $value = $record->{$field}?->toDateString() ?? ($field === 'work_date' ? request()->query('date') : null);
                @endphp
                <div>
                    <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                    @if ($field === 'effective_end_date')
                        <p id="schedule-end-hint" class="mt-1 text-sm text-muted">Tanggal akhir ikut dihitung. Kosongkan jika belum ada tanggal akhir.</p>
                    @endif
                    <input id="{{ $field }}" name="{{ $field }}" type="date" min="0001-01-01" @required($field !== 'effective_end_date')
                        value="{{ $formValue($field, $value) }}"
                        aria-describedby="{{ $field === 'effective_end_date' ? 'schedule-end-hint' : '' }} @error($field) {{ $field }}-error @enderror"
                        @error($field) aria-invalid="true" @enderror class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                    @error($field)<p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
                </div>
            @endforeach

            @unless ($defaultSchedule)
                <div>
                    <label for="office_id" class="block text-sm font-bold">Kantor shift khusus (opsional)</label>
                    <select id="office_id" name="office_id" class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                        @error('office_id') aria-invalid="true" aria-describedby="office-error" @enderror>
                        <option value="">Tanpa kantor khusus</option>
                        @foreach ($offices as $office)
                            <option value="{{ $office->id }}" @selected((string) $formValue('office_id', $record->office_id) === (string) $office->id)>{{ $office->name }}</option>
                        @endforeach
                    </select>
                    @error('office_id')<p id="office-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="status" class="block text-sm font-bold">Status</label>
                    <p id="schedule-status-hint" class="mt-1 text-sm text-muted">Status Dibatalkan mengembalikan jadwal default pada tanggal ini.</p>
                    <select id="status" name="status" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                        aria-describedby="schedule-status-hint @error('status') schedule-status-error @enderror"
                        @error('status') aria-invalid="true" @enderror>
                        @foreach (['scheduled' => 'Terjadwal', 'cancelled' => 'Dibatalkan'] as $value => $label)
                            <option value="{{ $value }}" @selected($formValue('status', $record->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<p id="schedule-status-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="reason" class="block text-sm font-bold">Alasan (opsional)</label>
                    <textarea id="reason" name="reason" rows="2" maxlength="2000" class="mt-1 w-full rounded-[10px] border border-control px-3 py-2"
                        @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror>{{ $formValue('reason', $record->reason) }}</textarea>
                    @error('reason')<p id="reason-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
                </div>
            @endunless

            @include('admin.employees.form-actions', ['submitLabel' => $defaultSchedule ? 'Simpan jadwal default' : 'Simpan shift khusus', 'cancelUrl' => $back])
        </form>
    </div>
@endsection
