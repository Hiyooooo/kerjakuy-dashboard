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
                    <span aria-hidden="true" class="relative grid size-9 shrink-0 place-items-center rounded-[12px] rounded-bl-[4px] bg-coral font-heading text-lg font-extrabold">K</span>
                    <div>
                        <span class="block font-heading text-xl font-extrabold tracking-tight">KerjaKuy</span>
                        <span class="block text-xs font-bold text-muted">Ruang kerja HR</span>
                    </div>
                </div>

                <nav aria-label="Navigasi admin" class="flex w-full flex-wrap items-center gap-1 md:block">
                    <a href="{{ route('admin.dashboard') }}"
                        @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif
                        class="flex min-h-11 items-center gap-3 rounded-[10px] px-3 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors">
                        <span aria-hidden="true" class="grid size-3.5 grid-cols-2 grid-rows-2 gap-0.5">
                            <span class="rounded-[2px] bg-current"></span>
                            <span class="rounded-[2px] bg-current"></span>
                            <span class="rounded-[2px] bg-current"></span>
                            <span class="rounded-[2px] bg-current"></span>
                        </span>
                        Dashboard
                    </a>
                    @canany(['admin.accounts.manage', 'admin.roles.manage', 'admin.permissions.manage'])
                        <p class="hidden px-3 text-xs font-extrabold text-muted md:mt-5 md:block">Akses</p>
                    @endcanany
                    @can('admin.accounts.manage')
                        <a href="{{ route('admin.accounts.index') }}"
                            @if (request()->routeIs('admin.accounts.*')) aria-current="page" @endif
                            class="flex min-h-11 items-center gap-2 rounded-[10px] px-2 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors md:mt-1 md:gap-3 md:px-3">
                            <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="9" cy="7" r="4"/><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2m1-18a4 4 0 0 1 0 8m5 10v-2a4 4 0 0 0-3-4"/>
                            </svg>
                            Pengguna
                        </a>
                    @endcan
                    @can('admin.roles.manage')
                        <a href="{{ route('admin.roles.index') }}"
                            @if (request()->routeIs('admin.roles.*')) aria-current="page" @endif
                            class="flex min-h-11 items-center gap-2 rounded-[10px] px-2 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors md:mt-1 md:gap-3 md:px-3">
                            <svg aria-hidden="true" class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 22s7-3 7-9V5l-7-3-7 3v8c0 6 7 9 7 9Z"/><path d="m9 12 2 2 4-4"/>
                            </svg>
                            Role
                        </a>
                    @endcan
                    @can('admin.permissions.manage')
                        <a href="{{ route('admin.permissions.index') }}"
                            @if (request()->routeIs('admin.permissions.*')) aria-current="page" @endif
                            class="mt-1 flex min-h-11 items-center rounded-[10px] px-3 text-sm font-bold hover:bg-paper aria-[current=page]:bg-coral/20 motion-safe:transition-colors">
                            Permission
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
