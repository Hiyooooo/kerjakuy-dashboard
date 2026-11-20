<nav aria-label="Profil pegawai" class="mt-3 flex flex-wrap gap-1 border-b border-line pb-2">
    @foreach ([
        ['admin.employees.edit', 'admin.employees.edit', 'Data dasar'],
        ['admin.employees.identity.edit', 'admin.employees.identity.*', 'Identitas'],
        ['admin.employees.family-members.index', 'admin.employees.family-members.*', 'Keluarga'],
        ['admin.employees.educations.index', 'admin.employees.educations.*', 'Pendidikan'],
        ['admin.employees.trainings.index', 'admin.employees.trainings.*', 'Pelatihan'],
        ['admin.employees.coachings.index', 'admin.employees.coachings.*', 'Pembinaan'],
        ['admin.employees.periods.index', 'admin.employees.periods.*', 'Kepegawaian'],
        ['admin.employees.assignments.index', 'admin.employees.assignments.*', 'Penempatan'],
        ['admin.employees.face-profiles.index', 'admin.employees.face-profiles.*', 'Referensi wajah'],
        ['admin.employees.schedules.index', ['admin.employees.schedules.*', 'admin.employees.schedule-assignments.*', 'admin.employees.shift-overrides.*'], 'Jadwal kerja'],
    ] as [$route, $pattern, $label])
        <a href="{{ route($route, $employee) }}" @if (request()->routeIs(...(array) $pattern)) aria-current="page" @endif
            class="inline-flex min-h-11 items-center rounded-t-[10px] border-b-2 border-transparent px-2.5 py-2 text-sm font-bold text-muted hover:bg-white hover:text-ink aria-[current=page]:border-control aria-[current=page]:bg-white aria-[current=page]:text-ink">{{ $label }}</a>
    @endforeach
</nav>
