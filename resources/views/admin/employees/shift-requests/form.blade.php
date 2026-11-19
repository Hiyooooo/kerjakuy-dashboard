@extends('layouts.admin')
@php
    $prefix = $overtime ? 'admin.overtime-requests' : 'admin.shift-swap-requests';
    $subject = $overtime ? 'permohonan lembur' : 'permohonan tukar shift';
    $fields = ['employee_code' => ['Kode pegawai', 'text']] + ($overtime ? [
        'overtime_date' => ['Tanggal mulai lembur', 'date'],
        'start_time' => ['Jam mulai (WIB)', 'time'],
        'end_time' => ['Jam selesai (WIB)', 'time'],
    ] : [
        'requester_work_date' => ['Tanggal kerja pemohon', 'date'],
        'target_employee_code' => ['Kode rekan tukar shift', 'text'],
        'target_work_date' => ['Tanggal kerja rekan', 'date'],
    ]);
@endphp
@section('title', 'Buat '.$subject)

@section('content')
    <a href="{{ route($prefix.'.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke {{ $subject }}</a>
    <h1 data-enter class="mt-2 font-heading text-[40px] leading-tight font-extrabold tracking-tight">Buat {{ $subject }}</h1>
    <form action="{{ route($prefix.'.store') }}" method="POST" class="mt-5 grid max-w-xl gap-4 rounded-[22px] border border-line bg-white p-5 sm:p-6">
        @csrf
        @unless ($overtime)
            <p class="text-sm text-muted">Tanggal kerja tiap pegawai tetap sama. Saat disetujui, shift efektif pada kedua tanggal itu saling ditukar.</p>
        @endunless
        @foreach ($fields as $field => [$label, $type])
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="{{ $type }}" required value="{{ is_scalar(old($field)) ? old($field) : '' }}"
                    @if ($type === 'text') maxlength="30" @endif
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error($field)<p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
            </div>
        @endforeach
        @if ($overtime)
            <label class="inline-flex min-h-11 items-center gap-3 text-sm font-bold">
                <input type="checkbox" name="ends_next_day" value="1" @checked(old('ends_next_day') === '1')
                    @error('ends_next_day') aria-invalid="true" aria-describedby="ends-next-day-error" @enderror>
                Selesai hari berikutnya
            </label>
            @error('ends_next_day')<p id="ends-next-day-error" class="text-sm text-[#b42318]">{{ $message }}</p>@enderror
        @endif
        <div>
            <label for="reason" class="block text-sm font-bold">Alasan{{ $overtime ? '' : ' (opsional)' }}</label>
            <textarea id="reason" name="reason" rows="3" maxlength="2000" @required($overtime) class="mt-1 w-full rounded-[10px] border border-control px-3 py-2"
                @error('reason') aria-invalid="true" aria-describedby="reason-error" @enderror>{{ is_string(old('reason')) ? old('reason') : '' }}</textarea>
            @error('reason')<p id="reason-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="min-h-12 rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 sm:justify-self-start">Simpan permohonan</button>
    </form>
@endsection
