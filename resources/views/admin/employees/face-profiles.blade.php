@extends('layouts.admin')

@section('title', 'Referensi wajah')

@section('content')
    <a href="{{ route('admin.employees.edit', $employee) }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke data pegawai</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">Referensi wajah</h1>
    <p class="mt-1 font-bold">{{ $employee->full_name }} · {{ $employee->employee_code }}</p>
    @if (session('employee_status'))
        <p role="status" class="mt-4 text-[#26734d]">{{ session('employee_status') }}</p>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.employees.face-profiles.store', $employee) }}"
        class="mt-6 grid max-w-xl gap-4 rounded-[22px] border border-line bg-white p-5 sm:p-6">
        @csrf
        <div>
            <label for="reference-photo" class="block text-sm font-bold">Foto referensi wajah</label>
            <p id="photo-hint" class="mt-1 text-sm text-muted">Pilih foto JPG atau PNG hingga 5 MB. Gunakan foto dengan wajah pegawai menghadap ke depan dan terlihat jelas.</p>
            <input id="reference-photo" name="photo" type="file" accept="image/jpeg,image/png" required
                aria-describedby="photo-hint @error('photo', route('admin.employees.face-profiles.index', $employee)) photo-error @enderror"
                @error('photo', route('admin.employees.face-profiles.index', $employee)) aria-invalid="true" @enderror
                class="mt-2 min-h-12 w-full min-w-0 rounded-[10px] border border-control text-sm file:mr-2 file:min-h-12 file:bg-paper file:px-3 file:font-bold">
            @error('photo', route('admin.employees.face-profiles.index', $employee))
                <p id="photo-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="min-h-12 w-fit cursor-pointer rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85">Unggah foto</button>
    </form>

    <h2 class="mt-8 font-heading text-xl font-extrabold">Riwayat unggahan</h2>
    <div class="mt-3 divide-y divide-line rounded-[22px] border border-line bg-white">
        @forelse ($profiles as $profile)
            <div class="flex flex-wrap items-center justify-between gap-x-4 p-4">
                <p>{{ $profile->uploaded_at->setTimezone('Asia/Jakarta')->locale('id')->translatedFormat('j F Y, H:i') }} WIB</p>
                <a href="{{ route('admin.employees.face-profiles.photo', [$employee, $profile]) }}" class="inline-flex min-h-11 items-center font-bold underline">Lihat foto</a>
            </div>
        @empty
            <p class="p-4 text-muted">Belum ada foto referensi.</p>
        @endforelse
    </div>
    @if ($profiles->hasPages())
        <div class="mt-5">{{ $profiles->links() }}</div>
    @endif
@endsection
