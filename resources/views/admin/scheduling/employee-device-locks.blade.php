@extends('layouts.admin')

@section('title', 'Penguncian perangkat')

@section('breadcrumb-parent')
    <a href="{{ route('admin.registered-devices.index') }}" class="inline-flex min-h-11 items-center hover:underline">Perangkat absensi</a>
@endsection

@section('content')
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">Penguncian perangkat</h1>
    <p class="mt-1 font-bold">{{ $device->device_name ?? 'Perangkat tanpa nama' }}</p>
    <p class="wrap-anywhere text-sm text-muted">{{ $device->device_uuid }}</p>

    @if (session('device_status'))
        <p role="status" class="mt-4 font-bold text-[#26734d]">{{ session('device_status') }}</p>
    @endif

    <section class="mt-6 max-w-xl rounded-[22px] border border-line bg-white p-5 sm:p-6">
        <h2 class="font-heading text-xl font-extrabold">Penguncian saat ini</h2>
        @if ($device->status === 'blocked')
            <p class="mt-2 text-sm text-muted">Perangkat diblokir. {{ $activeLock ? 'Penguncian aktif masih bisa dilepas.' : 'Perangkat ini tidak bisa dikunci.' }}</p>
        @endif

        @if ($activeLock)
            <p class="mt-2 font-bold">{{ $activeLock->employee->full_name }} · {{ $activeLock->employee->employee_code }}</p>
            <details class="mt-3">
                <summary class="min-h-11 w-fit cursor-pointer rounded-[10px] border border-control px-4 py-2.5 font-bold hover:bg-paper">Lepaskan perangkat</summary>
                <form method="POST" action="{{ route('admin.registered-devices.locks.release', [$device, $activeLock]) }}" class="mt-3">
                    @csrf
                    @method('PATCH')
                    <p id="release-hint" class="wrap-anywhere text-sm text-muted">Lepaskan {{ $device->device_name ?: $device->device_uuid }} dari {{ $activeLock->employee->full_name }}?</p>
                    <button type="submit" aria-describedby="release-hint" class="mt-2 min-h-11 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Konfirmasi pelepasan</button>
                </form>
            </details>
        @elseif ($device->status === 'registered')
            <form method="POST" action="{{ route('admin.registered-devices.locks.store', $device) }}" class="mt-4" data-employee-picker="{{ route('admin.registered-devices.locks.employees', $device) }}">
                @csrf
                <p class="mb-2 text-sm text-muted">Satu pegawai hanya bisa memiliki satu perangkat absensi yang terkunci aktif.</p>
                @include('admin.employees.employee-picker')
                <label for="employee_code" class="block text-sm font-bold">Kode pegawai</label>
                <input id="employee_code" name="employee_code" maxlength="30" required
                    value="{{ is_string(old('employee_code')) ? old('employee_code') : '' }}"
                    @error('employee_code') aria-invalid="true" aria-describedby="employee-code-error" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error('employee_code')
                    <p id="employee-code-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
                <button type="submit" class="mt-3 min-h-12 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Kunci perangkat</button>
            </form>
        @endif
        @if ($errors->has('employee_code') && ($activeLock || $device->status === 'blocked'))
            <p role="alert" class="mt-2 text-sm text-[#b42318]">{{ $errors->first('employee_code') }}</p>
        @endif
    </section>

    <section class="mt-8">
        <h2 class="font-heading text-xl font-extrabold">Riwayat penguncian</h2>
        <div class="mt-3 divide-y divide-line rounded-[22px] border border-line bg-white">
            @forelse ($locks as $lock)
                <div class="flex flex-wrap items-start justify-between gap-2 px-4 py-3">
                    <div>
                        <p class="font-bold">{{ $lock->employee->full_name }} · {{ $lock->employee->employee_code }}</p>
                        <p class="text-sm text-muted">Dikunci {{ $lock->locked_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') }} WIB · {{ $lock->locked_by_name ?? $lock->lockedBy?->name ?? 'Akun tidak tersedia' }}</p>
                        @if ($lock->released_at)
                            <p class="text-sm text-muted">Dilepas {{ $lock->released_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') }} WIB · {{ $lock->released_by_name ?? $lock->releasedBy?->name ?? 'Akun tidak tersedia' }}</p>
                        @endif
                    </div>
                    <p class="text-sm font-bold">{{ $lock->released_at ? 'Dilepas' : 'Aktif' }}</p>
                </div>
            @empty
                <p class="p-4 text-muted">Belum ada riwayat penguncian.</p>
            @endforelse
        </div>
        <div class="mt-3">{{ $locks->links() }}</div>
    </section>
@endsection
