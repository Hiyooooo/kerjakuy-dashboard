@extends('layouts.admin')
@section('title', 'Saldo cuti')

@section('content')
    <div class="admin-form-column">
        <a href="{{ route('admin.leave-requests.index') }}" class="inline-flex min-h-11 items-center font-bold underline">Kembali ke permohonan cuti</a>
        <h1 data-enter class="mt-2 admin-title">Saldo cuti</h1>
        @if (session('leave_status'))
            <p role="status" class="mt-3 text-[#26734d]">{{ session('leave_status') }}</p>
        @endif
        <form action="{{ route('admin.leave-requests.balances') }}" method="GET"
            data-employee-picker="{{ route('admin.leave-requests.employees') }}"
            class="mt-5 grid max-w-xl gap-4 admin-panel admin-form">
            @include('admin.employees.employee-picker')
            <label class="grid gap-1 text-sm font-bold">Kode pegawai
                <input name="employee_code" value="{{ is_string(old('employee_code', $filters['employee_code'] ?? '')) ? old('employee_code', $filters['employee_code'] ?? '') : '' }}" maxlength="30" required class="min-h-12 rounded-[10px] border border-control px-3">
            </label>
            @error('employee_code')<p role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>@enderror
            <div class="grid items-start gap-4 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)]">
                <div class="grid gap-1">
                    <label for="leave_category_id" class="text-sm font-bold">Kategori cuti</label>
                    <select id="leave_category_id" name="leave_category_id" required class="min-h-12 rounded-[10px] border border-control bg-white px-3"
                        @error('leave_category_id') aria-invalid="true" aria-describedby="balance-category-error" @enderror>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) ($filters['leave_category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('leave_category_id')<p id="balance-category-error" role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>@enderror
                </div>
                <div class="grid gap-1">
                    <label for="balance-year" class="text-sm font-bold">Tahun jatah</label>
                    <input id="balance-year" name="year" type="number" min="1" max="9999" value="{{ $filters['year'] ?? today('Asia/Jakarta')->year }}" required class="min-h-12 rounded-[10px] border border-control px-3"
                        @error('year') aria-invalid="true" aria-describedby="balance-year-error" @enderror>
                    @error('year')<p id="balance-year-error" role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>@enderror
                </div>
            </div>
            <button type="submit" class="admin-button admin-button--primary sm:justify-self-start">Tampilkan saldo</button>
        </form>

        @if ($employee)
            <section class="mt-6 max-w-xl admin-panel p-5">
                <h2 class="font-heading text-xl font-extrabold">{{ $employee->full_name }} · {{ $filters['year'] }}</h2>
                @if ($balance)
                    <dl class="mt-3 divide-y divide-line tabular-nums">
                        <div class="flex flex-wrap items-baseline justify-between gap-2 py-3 font-bold">
                            <dt>Saldo tersedia</dt>
                            <dd class="font-heading text-2xl font-extrabold">{{ number_format($balance->available_cents / 100, 2, ',', '.') }} hari</dd>
                        </div>
                        @foreach (['Jatah tahunan' => $balance->granted_days, 'Sisa bawaan' => $balance->carry_over_days, 'Penyesuaian' => $balance->adjusted_days ?? 0, 'Cuti disetujui' => $balance->approved_days ?? 0] as $label => $days)
                            <div class="flex flex-wrap justify-between gap-2 py-2">
                                <dt>{{ $label }}</dt>
                                <dd>{{ number_format((float) $days, 2, ',', '.') }} hari</dd>
                            </div>
                        @endforeach
                    </dl>
                    <h3 class="mt-5 border-t border-line pt-4 font-heading font-extrabold">Sesuaikan saldo cuti</h3>
                    <form action="{{ route('admin.leave-requests.balances.adjustments.store') }}" method="POST" class="mt-3 grid gap-3">
                        @csrf
                        <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                        <input type="hidden" name="leave_category_id" value="{{ $filters['leave_category_id'] }}">
                        <input type="hidden" name="year" value="{{ $filters['year'] }}">
                        <div>
                            <label for="adjustment_days" class="block text-sm font-bold">Penyesuaian (hari)</label>
                            <input id="adjustment_days" name="adjustment_days" type="number" min="-999.99" max="999.99" step="0.01" required
                                value="{{ is_scalar(old('adjustment_days')) ? old('adjustment_days') : '' }}" class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                                aria-describedby="adjustment-hint @error('adjustment_days') adjustment-error @enderror" @error('adjustment_days') aria-invalid="true" @enderror>
                            <p id="adjustment-hint" class="mt-1 text-sm text-muted">Masukkan angka positif untuk menambah atau angka negatif untuk mengurangi saldo.</p>
                            @error('adjustment_days')<p id="adjustment-error" role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="adjustment_reason" class="block text-sm font-bold">Alasan penyesuaian</label>
                            <textarea id="adjustment_reason" name="reason" rows="2" maxlength="2000" required class="mt-1 w-full rounded-[10px] border border-control px-3 py-2"
                                @error('reason') aria-invalid="true" aria-describedby="adjustment-reason-error" @enderror>{{ is_string(old('reason')) ? old('reason') : '' }}</textarea>
                            @error('reason')<p id="adjustment-reason-error" role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>@enderror
                        </div>
                        <button type="submit" class="admin-button admin-button--primary sm:justify-self-start">Simpan penyesuaian</button>
                    </form>
                @else
                    @error('adjustment_days')<p role="alert" class="mt-2 text-sm text-[#b42318]">{{ $message }}</p>@enderror
                    <p class="mt-2 text-sm text-muted">Jatah untuk kategori dan tahun ini belum tersedia.</p>
                @endif
            </section>
        @endif
    </div>
@endsection
