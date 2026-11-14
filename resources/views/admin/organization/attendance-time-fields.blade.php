<div>
    <label for="name" class="block text-sm font-bold">Nama waktu absen</label>
    <input id="name" name="name" value="{{ old('name', $record->name) }}" maxlength="100" required
        @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
        class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
    @error('name')
        <p id="name-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>

<fieldset aria-describedby="attendance-time-hint" class="grid gap-3">
    <legend class="mb-2 text-sm font-bold">Jam masuk dan pulang</legend>
    <p id="attendance-time-hint" class="text-sm text-muted">Jam pulang dan batas absen pulang yang lebih awal dari jam masuk dihitung pada hari berikutnya.</p>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ([
            'check_in_start' => 'Mulai absen masuk',
            'expected_check_in' => 'Jam masuk',
            'expected_check_out' => 'Jam pulang',
            'check_out_end' => 'Batas absen pulang (opsional)',
        ] as $field => $fieldLabel)
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $fieldLabel }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="time"
                    value="{{ old($field, substr($record->{$field} ?? '', 0, 5)) }}" @required($field !== 'check_out_end')
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                    class="mt-1 min-h-12 w-full min-w-0 rounded-[10px] border border-control px-3 tabular-nums">
                @error($field)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
</fieldset>

<div>
    <label for="late_tolerance_minutes" class="block text-sm font-bold">Toleransi terlambat (menit)</label>
    <input id="late_tolerance_minutes" name="late_tolerance_minutes" type="number" min="0" max="1439"
        value="{{ old('late_tolerance_minutes', $record->late_tolerance_minutes ?? 0) }}" required
        @error('late_tolerance_minutes') aria-invalid="true" aria-describedby="late_tolerance_minutes-error" @enderror
        class="mt-1 min-h-12 w-32 rounded-[10px] border border-control px-3 tabular-nums">
    @error('late_tolerance_minutes')
        <p id="late_tolerance_minutes-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>
