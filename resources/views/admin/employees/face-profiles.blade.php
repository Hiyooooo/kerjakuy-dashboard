@extends('layouts.admin')

@section('title', 'Referensi wajah')

@section('content')
    <div>
        @php
            $form = route('admin.employees.face-profiles.index', $employee);
            $expectedProfile = $errors->getBag($form)->any() ? old('active_profile_id') : $activeProfileId;
        @endphp
        <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke data pegawai</a>
        <h1 data-enter class="mt-2 admin-title">Referensi wajah</h1>
        <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
        @include('admin.employees.profile-nav')
        @if (session('employee_status'))
            <p role="status" class="mt-4 text-[#26734d]">{{ session('employee_status') }}</p>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{ route('admin.employees.face-profiles.store', $employee) }}"
            class="mt-6 grid gap-4 admin-panel admin-form">
            @csrf
            <input type="hidden" name="active_profile_id" value="{{ is_scalar($expectedProfile) ? $expectedProfile : '' }}">
            @error('active_profile_id', $form)
                <div class="text-sm text-[#b42318]">
                    <p role="alert">{{ $message }}</p>
                    <a href="{{ $form }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
                </div>
            @enderror
            <div class="max-w-xl">
                <label for="reference-photo" class="block text-sm font-bold">Foto referensi wajah</label>
                <p id="photo-hint" class="mt-1 text-sm text-muted">Pilih foto JPG atau PNG hingga 5 MB. Gunakan foto dengan wajah pegawai menghadap ke depan dan terlihat jelas.</p>
                <p id="photo-replacement-hint" class="mt-1 text-sm text-muted">
                    {{ $activeProfileId ? 'Foto aktif akan diganti. Foto sebelumnya tetap tersedia di riwayat.' : 'Foto yang diunggah akan menjadi referensi aktif.' }}
                </p>
                <input id="reference-photo" name="photo" type="file" accept="image/jpeg,image/png" required
                    aria-describedby="photo-hint photo-replacement-hint @error('photo', route('admin.employees.face-profiles.index', $employee)) photo-error @enderror"
                    @error('photo', route('admin.employees.face-profiles.index', $employee)) aria-invalid="true" @enderror
                    class="mt-2 min-h-12 w-full min-w-0 rounded-[10px] border border-control text-sm file:mr-2 file:min-h-12 file:bg-paper file:px-3 file:font-bold">
                @error('photo', route('admin.employees.face-profiles.index', $employee))
                    <p id="photo-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
            @include('admin.employees.form-actions', ['submitLabel' => $activeProfileId ? 'Ganti foto aktif' : 'Unggah foto referensi', 'cancelUrl' => route('admin.employees.edit', $employee)])
        </form>

        <h2 class="mt-8 font-heading text-xl font-extrabold">Riwayat unggahan</h2>
        <div class="mt-3 divide-y divide-line admin-panel">
            @forelse ($profiles as $profile)
                <div class="flex flex-wrap items-center justify-between gap-x-4 p-4">
                    <div>
                        <p class="text-sm font-bold">{{ $profile->deactivated_at === null ? 'Aktif' : 'Nonaktif' }}</p>
                        <p class="text-sm text-muted">{{ $profile->uploaded_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') }} WIB</p>
                    </div>
                    <a href="{{ route('admin.employees.face-profiles.photo', [$employee, $profile]) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat foto</a>
                </div>
            @empty
                <p class="p-4 text-muted">Belum ada foto referensi.</p>
            @endforelse
        </div>
        @if ($profiles->hasPages())
            <div class="mt-5">{{ $profiles->links() }}</div>
        @endif
    </div>
@endsection
