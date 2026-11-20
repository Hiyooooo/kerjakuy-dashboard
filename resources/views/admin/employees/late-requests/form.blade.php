@extends('layouts.admin')
@section('title', 'Buat permohonan terlambat')

@section('content')
    <div class="admin-form-column">
        <a href="{{ route('admin.late-requests.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke permohonan terlambat</a>
        <h1 data-enter class="mt-2 admin-title">Buat permohonan terlambat</h1>
        <form action="{{ route('admin.late-requests.store') }}" method="POST" enctype="multipart/form-data" class="mt-5 grid max-w-xl gap-4 admin-panel admin-form">
            @csrf
            <p class="text-sm text-muted">Jika catatan absensi tersedia untuk tanggal kerja ini, sistem menautkannya dan memakai jam yang tercatat. Isi jam secara manual bila datanya belum tersedia. Gunakan WIB.</p>
            @foreach ([
                'employee_code' => ['Kode pegawai', 'text', true],
                'late_date' => ['Tanggal kerja', 'date', true],
                'expected_check_in' => ['Jadwal masuk (opsional)', 'time', false],
                'actual_check_in' => ['Jam masuk aktual (opsional)', 'time', false],
                'attachment' => ['Lampiran PDF, JPG, atau PNG (opsional)', 'file', false],
            ] as $field => [$label, $type, $required])
                <div>
                    <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" @required($required)
                        @if ($type === 'file') accept=".pdf,.jpg,.jpeg,.png" @else value="{{ is_scalar(old($field)) ? old($field) : '' }}" @endif
                        @if ($field === 'employee_code') maxlength="30" @endif
                        @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                        class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3 py-2">
                    @error($field)<p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div>
                <label for="reason" class="block text-sm font-bold">Alasan keterlambatan</label>
                <textarea id="reason" name="reason" rows="3" maxlength="2000" required class="mt-1 w-full rounded-[10px] border border-control px-3 py-2"
                    @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror>{{ is_string(old('reason')) ? old('reason') : '' }}</textarea>
                @error('reason')<p id="reason-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="admin-button admin-button--primary sm:justify-self-start">Simpan permohonan</button>
        </form>
    </div>
@endsection
