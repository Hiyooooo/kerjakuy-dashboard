@extends('layouts.admin')

@section('title', 'Perangkat absensi')

@section('content')
    <h1 data-enter class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Perangkat absensi</h1>

    @if (session('device_status'))
        <p role="status" class="mt-4 font-bold text-[#26734d]">{{ session('device_status') }}</p>
    @endif

    <form method="GET" action="{{ route('admin.registered-devices.index') }}" aria-label="Filter perangkat" class="mt-6 flex flex-wrap items-end gap-3">
        <label class="grid w-full max-w-xs gap-1 text-sm font-bold">Nama atau UUID perangkat
            <input type="search" name="search" maxlength="150" value="{{ $filters['search'] ?? '' }}" class="min-h-11 min-w-0 rounded-[10px] border border-control bg-white px-3">
        </label>
        <label class="grid gap-1 text-sm font-bold">Status
            <select name="status" class="min-h-11 rounded-[10px] border border-control bg-white px-3">
                <option value="">Semua status</option>
                @foreach (['registered' => 'Terdaftar', 'blocked' => 'Diblokir'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="min-h-11 rounded-[10px] border border-control bg-white px-4 font-bold hover:bg-paper">Terapkan filter</button>
        @if (array_filter($filters))
            <a href="{{ route('admin.registered-devices.index') }}" class="inline-flex min-h-11 items-center px-2 font-bold underline">Reset filter</a>
        @endif
    </form>

    <details class="mt-6 max-w-xl rounded-[22px] border border-line bg-white px-5 py-2 sm:px-6" @if ($errors->any()) open @endif>
        <summary class="min-h-11 cursor-pointer py-3 font-bold">Daftarkan perangkat</summary>
        <form method="POST" action="{{ route('admin.registered-devices.store') }}" class="grid gap-4 pb-4">
            @csrf
            @foreach ([
                'device_uuid' => ['UUID perangkat', 36],
                'device_name' => ['Nama perangkat (opsional)', 150],
                'platform' => ['Platform (opsional)', 50],
                'app_version' => ['Versi aplikasi (opsional)', 50],
            ] as $field => [$label, $maxLength])
                <div>
                    <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                    @if ($field === 'device_uuid')
                        <p id="device-uuid-hint" class="mt-1 text-sm text-muted">Gunakan UUID dari aplikasi di perangkat yang akan dipakai untuk absensi.</p>
                    @endif
                    @php($previous = old($field))
                    <input type="text" id="{{ $field }}" name="{{ $field }}" value="{{ is_string($previous) ? $previous : '' }}"
                        maxlength="{{ $maxLength }}" @required($field === 'device_uuid')
                        aria-describedby="{{ $field === 'device_uuid' ? 'device-uuid-hint' : '' }} @error($field) {{ $field }}-error @enderror"
                        @error($field) aria-invalid="true" @enderror
                        class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                    @error($field)
                        <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
            <button type="submit" class="min-h-12 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 sm:justify-self-start">Daftarkan perangkat</button>
        </form>
    </details>

    <section class="mt-8">
        <h2 class="font-heading text-xl font-extrabold">Daftar perangkat</h2>
        <div class="mt-3 divide-y divide-line rounded-[22px] border border-line bg-white">
            @forelse ($devices as $device)
                <div class="flex flex-wrap items-start justify-between gap-2 px-4 py-3">
                    <div class="min-w-0 wrap-anywhere">
                        <p class="font-bold">{{ $device->device_name ?? 'Perangkat tanpa nama' }}</p>
                        <p class="text-sm text-muted">{{ $device->device_uuid }}</p>
                        <p class="mt-1 text-sm">{{ $device->activeLock ? 'Pegawai terhubung: '.$device->activeLock->employee->full_name.' · '.$device->activeLock->employee->employee_code : 'Belum terhubung ke pegawai' }}</p>
                        <p class="text-sm text-muted">{{ $device->registered_by_name ? 'Didaftarkan oleh '.$device->registered_by_name : 'Pendaftar tidak tercatat' }}</p>
                        @if ($device->platform || $device->app_version)
                            <p class="text-sm text-muted">{{ implode(' · ', array_filter([$device->platform, $device->app_version ? 'Versi '.$device->app_version : null])) }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <p @class(['rounded-full px-2 py-1 text-xs font-bold', 'bg-coral/15 text-[#b42318]' => $device->status === 'blocked', 'bg-green-50 text-[#26734d]' => $device->status === 'registered'])>{{ $device->status === 'blocked' ? 'Diblokir' : 'Terdaftar' }}</p>
                        <a href="{{ route('admin.registered-devices.locks.index', $device) }}"
                            aria-label="Penguncian perangkat {{ $device->device_uuid }}"
                            class="inline-flex min-h-11 items-center px-3 text-sm font-bold underline">Kelola penguncian</a>
                        @if ($device->status === 'registered')
                            <details>
                                <summary class="min-h-11 cursor-pointer px-2 py-3 text-sm font-bold text-[#b42318]">Blokir</summary>
                                <form method="POST" action="{{ route('admin.registered-devices.block', $device) }}" class="max-w-xs space-y-2 rounded-[10px] border border-line bg-paper p-3">
                                    @csrf
                                    @method('PATCH')
                                    <p class="wrap-anywhere text-sm">{{ $device->device_name ?: $device->device_uuid }} tidak dapat dipakai untuk absensi setelah diblokir.</p>
                                    <button type="submit" class="min-h-11 rounded-[10px] border border-[#b42318] px-3 text-sm font-bold text-[#b42318] hover:bg-white">Konfirmasi blokir</button>
                                </form>
                            </details>
                        @endif
                    </div>
                </div>
            @empty
                <p class="p-4 text-muted">{{ array_filter($filters) ? 'Tidak ada perangkat yang cocok dengan filter.' : 'Belum ada perangkat terdaftar.' }}</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $devices->links() }}</div>
    </section>
@endsection
