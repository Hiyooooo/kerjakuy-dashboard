<nav aria-label="Profil pegawai" class="mt-3 flex flex-wrap gap-1 border-b border-line pb-2">
    @foreach ([
        ['admin.employees.edit', 'admin.employees.edit', 'Data dasar'],
        ['admin.employees.identity.edit', 'admin.employees.identity.*', 'Identitas'],
        ['admin.employees.periods.index', 'admin.employees.periods.*', 'Kepegawaian'],
        ['admin.employees.assignments.index', 'admin.employees.assignments.*', 'Penempatan'],
        ['admin.employees.face-profiles.index', 'admin.employees.face-profiles.*', 'Referensi wajah'],
        ['admin.employees.schedules.index', ['admin.employees.schedules.*', 'admin.employees.schedule-assignments.*', 'admin.employees.shift-overrides.*'], 'Jadwal kerja'],
    ] as [$route, $pattern, $label])
        <a href="{{ route($route, $employee) }}" @if (request()->routeIs(...(array) $pattern)) aria-current="page" @endif
            class="inline-flex min-h-11 items-center rounded-[10px] px-3 text-sm font-bold hover:bg-white aria-[current=page]:bg-coral/20 aria-[current=page]:underline">{{ $label }}</a>
    @endforeach
</nav>
