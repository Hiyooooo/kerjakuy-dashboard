@extends('layouts.admin')

@section('title', 'Perangkat absensi')

@section('content')
    <h1 data-enter class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Perangkat absensi</h1>

    @if (session('device_status'))
        <p role="status" class="mt-4 font-bold text-[#26734d]">{{ session('device_status') }}</p>
    @endif

    <section class="mt-6 max-w-xl rounded-[22px] border border-line bg-white p-5 sm:p-6">
        <h2 class="font-heading text-xl font-extrabold">Daftarkan perangkat</h2>
        <form method="POST" action="{{ route('admin.registered-devices.store') }}" class="mt-4 grid gap-4">
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
    </section>

    <section class="mt-8">
        <h2 class="font-heading text-xl font-extrabold">Daftar perangkat</h2>
        <div class="mt-3 divide-y divide-line rounded-[22px] border border-line bg-white">
            @forelse ($devices as $device)
                <div class="flex flex-wrap items-start justify-between gap-2 px-4 py-3">
                    <div class="min-w-0 wrap-anywhere">
                        <p class="font-bold">{{ $device->device_name ?? 'Perangkat tanpa nama' }}</p>
                        <p class="text-sm text-muted">{{ $device->device_uuid }}</p>
                        <p class="text-sm text-muted">{{ $device->registered_by_name ? 'Didaftarkan oleh '.$device->registered_by_name : 'Pendaftar tidak tercatat' }}</p>
                        @if ($device->platform || $device->app_version)
                            <p class="text-sm text-muted">{{ implode(' · ', array_filter([$device->platform, $device->app_version ? 'Versi '.$device->app_version : null])) }}</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <p class="text-sm font-bold">{{ $device->status === 'blocked' ? 'Diblokir' : 'Terdaftar' }}</p>
                        <a href="{{ route('admin.registered-devices.locks.index', $device) }}"
                            aria-label="Penguncian perangkat {{ $device->device_uuid }}"
                            class="inline-flex min-h-11 items-center px-3 text-sm font-bold underline">Penguncian</a>
                        @if ($device->status === 'registered')
                            <form method="POST" action="{{ route('admin.registered-devices.block', $device) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="min-h-11 rounded-[10px] px-3 text-sm font-bold text-[#b42318] hover:bg-paper" aria-label="Blokir perangkat {{ $device->device_uuid }}">Blokir</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="p-4 text-muted">Belum ada perangkat terdaftar.</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $devices->links() }}</div>
    </section>
@endsection
