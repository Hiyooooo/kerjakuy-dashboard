@extends('layouts.admin')

@php
    $title = $record ? 'Detail absensi' : 'Catat absensi masuk';
@endphp
@section('title', $title)

@section('content')
    <a href="{{ route('admin.attendance-records.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke catatan absensi</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $title }}</h1>
    @if (session('attendance_status'))
        <p role="status" class="mt-4 font-bold text-[#26734d]">{{ session('attendance_status') }}</p>
    @endif

    @if ($record)
        @php
            $displayTime = fn ($time) => $time->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j M Y, H:i').' WIB';
        @endphp
        <section class="mt-6 rounded-[22px] border border-line bg-white p-5">
            <h2 class="font-heading text-xl font-extrabold">{{ $record->employee->full_name }} · {{ $record->work_date->locale('id')->translatedFormat('j F Y') }}</h2>
            <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    'Shift' => $record->shift->name,
                    'Metode' => $record->method->name,
                    'Lokasi acuan' => $record->location?->name,
                    'Perangkat' => $record->device?->device_name ?: $record->device?->device_uuid,
                    'Jadwal masuk' => $displayTime($record->scheduled_check_in_at),
                    'Jadwal pulang' => $displayTime($record->scheduled_check_out_at),
                    'Batas absen pulang' => $record->check_out_deadline_at ? $displayTime($record->check_out_deadline_at) : null,
                    'Radius acuan' => $record->location_radius_snapshot !== null ? $record->location_radius_snapshot.' m' : null,
                    'Masuk' => $record->check_in_at ? $displayTime($record->check_in_at) : null,
                    'Pulang' => $record->check_out_at ? $displayTime($record->check_out_at) : null,
                    'Keterlambatan' => $record->late_minutes.' menit',
                    'Jarak saat masuk' => $record->distance_meter !== null ? number_format($record->distance_meter, 2, ',', '.').' m' : null,
                    'Jarak saat pulang' => $record->check_out_distance_meter !== null ? number_format($record->check_out_distance_meter, 2, ',', '.').' m' : null,
                    'Alasan di luar lokasi saat masuk' => $record->checkInReason?->name,
                    'Catatan masuk' => $record->out_of_location_note,
                    'Alasan di luar lokasi saat pulang' => $record->checkOutReason?->name,
                    'Catatan pulang' => $record->check_out_location_note,
                ] as $label => $value)
                    <div class="min-w-0 wrap-anywhere">
                        <dt class="text-sm font-bold">{{ $label }}</dt>
                        <dd class="text-muted">{{ $value ?? '—' }}</dd>
                    </div>
                @endforeach
            </dl>
            @can('admin.attendance.photos.view')
            <div class="mt-3 flex flex-wrap gap-4">
                @foreach (['masuk' => $record->check_in_selfie_path, 'pulang' => $record->check_out_selfie_path] as $event => $path)
                    @if ($path)
                        <a href="{{ route('admin.attendance-records.photo', [$record, $event]) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat selfie {{ $event }}</a>
                    @endif
                @endforeach
            </div>
            @endcan
        </section>
    @endif

    @if (! $record || ($record->check_in_at && ! $record->check_out_at))
        <section class="mt-6 max-w-xl rounded-[22px] border border-line bg-white p-5 sm:p-6">
            @if ($record)
                <h2 class="mb-3 font-heading text-xl font-extrabold">Catat absensi pulang</h2>
                <p class="mb-3 text-sm text-muted">Absensi pulang memakai perangkat yang sama dan radius acuan yang tersimpan pada catatan masuk.</p>
            @else
                <p class="mb-3 text-sm text-muted">Gunakan waktu WIB dan pilih tanggal kerja sesuai jadwal. Lokasi acuan harus berlaku untuk kantor jadwal atau semua kantor. Form manual memakai metode tanpa verifikasi wajah.</p>
            @endif
            <form method="POST" enctype="multipart/form-data" class="grid gap-4"
                action="{{ $record ? route('admin.attendance-records.checkout', $record) : route('admin.attendance-records.store') }}">
                @csrf
                @if ($record)
                    @method('PATCH')
                @endif
                @php
                    $fields = [
                        'recorded_at' => [$record ? 'Waktu pulang (WIB)' : 'Waktu masuk (WIB)', 'datetime-local', true],
                        'latitude' => ['Lintang', 'number', false],
                        'longitude' => ['Bujur', 'number', false],
                        'selfie' => ['Foto selfie (opsional)', 'file', false],
                        'reason_id' => ['Alasan di luar radius', 'select', false, $reasons->pluck('name', 'id')],
                        'location_note' => ['Catatan di luar radius', 'textarea', false],
                    ];
                    if (! $record) {
                        $fields = [
                            'employee_code' => ['Kode pegawai', 'text', true],
                            'work_date' => ['Tanggal kerja', 'date', true],
                            'method_id' => ['Metode absensi', 'select', true, $methods->pluck('name', 'id')],
                            'location_id' => ['Lokasi acuan', 'select', false, $locations->pluck('name', 'id')],
                            'device_id' => ['Perangkat terdaftar', 'select', false, $devices->mapWithKeys(fn ($device) => [$device->id => trim(($device->device_name ? $device->device_name.' · ' : '').$device->device_uuid)])],
                            ...$fields,
                        ];
                    }
                @endphp
                @foreach ($fields as $field => $definition)
                    @php
                        [$label, $type, $required] = $definition;
                        $previous = is_scalar(old($field)) ? old($field) : '';
                        $classes = 'mt-1 min-h-11 w-full rounded-[10px] border border-control bg-white px-3';
                    @endphp
                    <div>
                        <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                        @if ($type === 'select')
                            <select id="{{ $field }}" name="{{ $field }}" class="{{ $classes }}" @required($required)
                                @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                                <option value="">{{ $required ? 'Pilih metode' : 'Tidak dipilih' }}</option>
                                @foreach ($definition[3] as $id => $name)
                                    <option value="{{ $id }}" @selected((string) $previous === (string) $id)>{{ $name }}</option>
                                @endforeach
                            </select>
                        @elseif ($type === 'textarea')
                            <textarea id="{{ $field }}" name="{{ $field }}" rows="2" maxlength="2000" class="{{ $classes }} py-2"
                                @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>{{ $previous }}</textarea>
                        @else
                            <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" class="{{ $classes }} py-2" @required($required)
                                @if ($type === 'file') accept="image/jpeg,image/png" @else value="{{ $previous }}" @endif
                                @if ($type === 'number') step="any" @endif
                                @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        @endif
                        @if ($field === 'latitude')
                            <p class="mt-1 text-sm text-muted">Isi lintang dan bujur jika menggunakan lokasi acuan.</p>
                        @endif
                        @error($field)<p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <button type="submit" class="min-h-12 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 sm:justify-self-start">{{ $record ? 'Simpan absensi pulang' : 'Simpan absensi masuk' }}</button>
            </form>
        </section>
    @endif
@endsection
