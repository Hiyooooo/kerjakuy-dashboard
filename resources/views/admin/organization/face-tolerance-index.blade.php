@extends('layouts.admin')

@section('title', 'Toleransi wajah')

@section('content')
    <div data-enter class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">Toleransi wajah</h1>
            <p class="mt-2 text-muted">Atur skor minimum kecocokan wajah untuk setiap periode absensi.</p>
        </div>
        <a href="{{ route('admin.face-tolerance-policies.create') }}" class="inline-flex min-h-12 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">Tambah kebijakan</a>
    </div>

    @if (session('organization_status'))
        <p role="status" class="mt-5 rounded-[10px] border border-line bg-white px-4 py-3 text-[#26734d]">{{ session('organization_status') }}</p>
    @endif

    @if ($policies->isEmpty())
        <p class="mt-6 rounded-[22px] border border-line bg-white p-6 text-muted">Belum ada kebijakan toleransi wajah.</p>
    @else
        <div class="mt-6 overflow-hidden rounded-[22px] border border-line bg-white">
            <table class="w-full table-fixed text-left text-sm">
                <thead class="border-b border-line bg-paper">
                    <tr>
                        <th scope="col" class="px-3 py-3 sm:px-5">Skor minimum dan periode</th>
                        <th scope="col" class="w-24 px-2 py-3 sm:w-36 sm:px-5">Status</th>
                        <th scope="col" class="w-16 px-2 py-3 text-right sm:w-24 sm:px-5">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($policies as $policy)
                        @php
                            $starts = $policy->effective_start_date;
                            $ends = $policy->effective_end_date;
                            $status = match (true) {
                                $starts->toDateString() > $today => 'Terjadwal',
                                $ends && $ends->toDateString() < $today => 'Berakhir',
                                default => 'Berlaku',
                            };
                        @endphp
                        <tr class="focus-within:bg-paper/60 hover:bg-paper/60">
                            <th scope="row" class="px-3 py-3 sm:px-5">
                                <span class="tabular-nums">{{ str_replace('.', ',', rtrim(rtrim($policy->minimum_match_score, '0'), '.')) }} / 100</span>
                                <span class="mt-1 block font-normal text-muted">{{ $starts->locale('id')->translatedFormat('j F Y') }} · {{ $ends?->locale('id')->translatedFormat('j F Y') ?? 'Tanpa batas akhir' }}</span>
                            </th>
                            <td class="px-2 py-3 sm:px-5">
                                <span @class(['rounded-[14px] px-2 py-1 text-xs font-bold', 'bg-[#e9f5ee] text-[#26734d]' => $status === 'Berlaku', 'bg-paper text-muted' => $status !== 'Berlaku'])>{{ $status }}</span>
                            </td>
                            <td class="px-2 py-3 text-right sm:px-5">
                                <a href="{{ route('admin.face-tolerance-policies.edit', $policy) }}" aria-label="Edit kebijakan mulai {{ $starts->locale('id')->translatedFormat('j F Y') }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[10px] px-2 font-bold underline decoration-control/50 underline-offset-4 hover:bg-paper">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($policies->hasPages())
            <div class="mt-5">{{ $policies->links() }}</div>
        @endif
    @endif
@endsection
