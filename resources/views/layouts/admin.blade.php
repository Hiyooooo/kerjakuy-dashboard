<!doctype html>
<html lang="id" data-type="software">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fff8f3">
    <meta name="page-type" content="software">
    <meta name="site-kind" content="software">
    <meta name="description" content="Ruang kerja internal admin dan HR KerjaKuy.">
    <title>@yield('title') | KerjaKuy</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-paper font-sans text-ink">
    <a href="#konten" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-10 focus:rounded-[10px] focus:bg-white focus:px-4 focus:py-3">
        Lewati ke konten
    </a>

    <div class="min-h-screen md:grid md:grid-cols-[15rem_minmax(0,1fr)]">
        <aside class="border-b border-line bg-white px-[18px] py-4 md:border-r md:border-b-0 md:px-6 md:py-8">
            <div class="flex flex-wrap items-center justify-between gap-3 md:items-start md:gap-8 md:flex-col">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('icon.svg') }}" alt="" width="36" height="36" class="size-9 shrink-0">
                    <div>
                        <span class="block font-heading text-xl font-extrabold tracking-tight">KerjaKuy</span>
                        <span class="block text-xs font-bold text-muted">Ruang kerja HR</span>
                    </div>
                </div>

                <nav aria-label="Navigasi admin" class="w-full space-y-1">
                    <a href="{{ route('admin.dashboard') }}"
                        @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
                        class="flex min-h-11 items-center gap-2 rounded-[10px] px-2 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors md:gap-3 md:px-3">
                        <span aria-hidden="true" class="grid size-4 shrink-0 grid-cols-2 grid-rows-2 gap-0.5 p-px">
                            <span class="rounded-[2px] bg-current"></span>
                            <span class="rounded-[2px] bg-current"></span>
                            <span class="rounded-[2px] bg-current"></span>
                            <span class="rounded-[2px] bg-current"></span>
                        </span>
                        Dashboard
                    </a>
                    @can('admin.accounts.manage')
                        <a href="{{ route('admin.accounts.index') }}"
                            @if (request()->routeIs('admin.accounts.*')) aria-current="page" @endif
                            class="flex min-h-11 items-center gap-2 rounded-[10px] px-2 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors md:mt-1 md:gap-3 md:px-3">
                            <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="7" r="4"/><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2m1-18a4 4 0 0 1 0 8m5 10v-2a4 4 0 0 0-3-4"/>
                            </svg>
                            Akses pengguna
                        </a>
                    @endcan
                    @can('admin.employees.manage')
                        <p class="mt-4 px-3 text-xs font-extrabold text-muted">HRD</p>
                        <a href="{{ route('admin.employees.index') }}"
                            @if (request()->routeIs('admin.employees.*')) aria-current="page" @endif
                            class="flex min-h-11 items-center gap-2 rounded-[10px] px-2 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors md:mt-1 md:gap-3 md:px-3">
                            <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="5" width="20" height="14" rx="2"/>
                                <circle cx="8" cy="10" r="2"/>
                                <path d="M5 16a3 3 0 0 1 6 0m3-6h4m-4 4h4"/>
                            </svg>
                            Data pegawai
                        </a>
                    @endcan
                    @can('admin.organization.manage')
                        @php($menuGroups = [
                            'Master data' => [
                            'positions' => ['Jabatan', 'M3 7h18v14H3z M8 7V3h8v4 M3 13h18 M10 13v2h4v-2'],
                            'offices' => ['Kantor', 'M4 21V4h12v17 M16 10h4v11 M2 21h20 M8 8h4 M8 12h4 M8 16h4'],
                            'job-desks' => ['Job desk', 'M6 3h9l4 4v14H6z M14 3v5h5 M9 12h7 M9 16h7'],
                            'leave-categories' => ['Kategori cuti', 'M3 12V4h8l10 10-7 7z M7 8h.01'],
                            'holidays' => ['Hari libur', 'M3 6h18v15H3z M7 3v6 M17 3v6 M3 11h18 M9 16l2 2 4-4'],
                            ],
                            'Absensi' => [
                            'attendance-times' => ['Waktu absen', 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z M12 7v5l3 2'],
                            'out-of-location-reasons' => ['Alasan di luar lokasi', 'M21 4H3v13h5v4l5-4h8z M7 9h10 M7 13h6'],
                            'attendance-methods' => ['Metode absen', 'M4 6h16M4 12h16M4 18h16 M8 4v4 M16 10v4 M10 16v4'],
                            'face-tolerance-policies' => ['Toleransi wajah', 'M8 3H3v5 M16 3h5v5 M21 16v5h-5 M3 16v5h5 M9 10h.01 M15 10h.01 M9 15q3 3 6 0'],
                            'attendance-locations' => ['Lokasi absen', 'M20 10c0 5-8 11-8 11s-8-6-8-11a8 8 0 1 1 16 0Z M15 10a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
                            ],
                            'Jadwal kerja' => [
                            'work-shifts' => ['Shift kerja', 'M12 6v6l4 2 M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                            'work-schedule-templates' => ['Template jadwal mingguan', 'M3 6h18v15H3z M7 3v6 M17 3v6 M3 11h18'],
                            ],
                        ])
                        @foreach ($menuGroups as $groupLabel => $items)
                            <details class="group" @if (request()->routeIs(...array_map(fn ($route) => 'admin.'.$route.'.*', array_keys($items)))) open @endif>
                                <summary class="flex min-h-11 cursor-pointer list-none items-center justify-between gap-2 rounded-[10px] px-3 text-sm font-extrabold hover:bg-paper [&::-webkit-details-marker]:hidden">
                                    {{ $groupLabel }}
                                    <svg aria-hidden="true" class="size-4 shrink-0 group-open:rotate-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 6 6 6-6 6"/>
                                    </svg>
                                </summary>
                                <div class="ml-3 space-y-1 border-l border-line pl-2">
                                    @foreach ($items as $route => [$label, $iconPath])
                                        <a href="{{ route('admin.'.$route.'.index') }}"
                                            @if (request()->routeIs('admin.'.$route.'.*')) aria-current="page" @endif
                                            class="flex min-h-11 items-center gap-2 rounded-[10px] px-2 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors">
                                            <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="{{ $iconPath }}"/>
                                            </svg>
                                            {{ $label }}
                                        </a>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    @endcan
                    @can('admin.attendance.manage')
                        <a href="{{ route('admin.attendance-records.index') }}"
                            @if (request()->routeIs('admin.attendance-records.*')) aria-current="page" @endif
                            class="flex min-h-11 items-center gap-2 rounded-[10px] px-2 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 md:px-3">
                            <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="4" y="4" width="16" height="16" rx="2"/><path d="m8 12 3 3 5-6"/>
                            </svg>
                            Catatan absensi
                        </a>
                    @endcan
                    @can('admin.devices.manage')
                        <a href="{{ route('admin.registered-devices.index') }}"
                            @if (request()->routeIs('admin.registered-devices.*')) aria-current="page" @endif
                            class="flex min-h-11 items-center gap-2 rounded-[10px] px-2 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors md:mt-1 md:gap-3 md:px-3">
                            <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                <rect x="6" y="2" width="12" height="20" rx="2"/><path d="M11 18h2"/>
                            </svg>
                            Perangkat absensi
                        </a>
                    @endcan
                </nav>
            </div>
        </aside>

        <div>
            <header class="border-b border-line bg-white">
                <div class="mx-auto flex min-h-16 max-w-[1600px] items-center justify-between gap-3 px-[18px] text-sm font-bold text-muted md:px-8 xl:px-12">
                    @hasSection('breadcrumb-parent')
                        <nav aria-label="Breadcrumb" class="flex flex-wrap items-center gap-x-2">
                            @yield('breadcrumb-parent')
                            <span aria-hidden="true">/</span>
                            <span aria-current="page" class="text-ink">@yield('title')</span>
                        </nav>
                    @else
                        <span class="text-ink">@yield('title')</span>
                    @endif
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="min-h-11 cursor-pointer rounded-[10px] px-3 text-ink hover:bg-paper motion-safe:transition-colors">
                            Keluar
                        </button>
                    </form>
                </div>
            </header>
            <main id="konten" tabindex="-1" class="mx-auto max-w-[1600px] px-[18px] py-8 md:px-8 md:py-10 xl:px-12">
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
