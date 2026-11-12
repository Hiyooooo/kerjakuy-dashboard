@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <h1 class="font-heading text-[40px] leading-[1.03] font-extrabold tracking-[-0.04em] md:text-[56px]">Dashboard</h1>

    <section aria-labelledby="ringkasan" data-enter class="mt-8 overflow-hidden rounded-[22px] border border-line bg-white">
        <div class="border-b border-line px-6 py-4 md:px-8">
            <h2 id="ringkasan" class="font-heading text-xl font-bold tracking-tight">Ringkasan operasional</h2>
        </div>

        <div class="flex flex-col items-start gap-5 px-6 py-8 sm:flex-row sm:items-center md:px-8 md:py-12">
            <div aria-hidden="true" class="relative grid size-14 shrink-0 place-items-center rounded-[20px] rounded-bl-[5px] bg-coral">
                <span class="flex items-center gap-1.5">
                    <span class="size-1.5 rounded-full bg-ink"></span>
                    <span class="size-1.5 rounded-full bg-ink"></span>
                    <span class="size-1.5 rounded-full bg-ink"></span>
                </span>
                <span class="absolute -top-1 -right-1 size-3 rounded-full bg-amber ring-2 ring-white"></span>
            </div>
            <div>
                <h3 class="font-heading text-lg font-bold">Belum ada data operasional</h3>
                <p class="mt-1 max-w-prose text-muted">Ringkasan pegawai, absensi, dan permohonan akan muncul setelah modul terkait tersedia.</p>
            </div>
        </div>
    </section>
@endsection
