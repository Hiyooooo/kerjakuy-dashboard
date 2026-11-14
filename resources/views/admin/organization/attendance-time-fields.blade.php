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
    <p id="attendance-time-hint" class="text-sm text-muted">Hari kerja dimulai pada tanggal jam masuk. Pilih hari sebelumnya atau berikutnya jika waktu absen melewati tengah malam.</p>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ([
            'check_in_start' => 'Mulai absen masuk',
            'expected_check_in' => 'Jam masuk',
            'expected_check_out' => 'Jam pulang',
            'check_out_end' => 'Batas absen pulang (opsional)',
        ] as $field => $fieldLabel)
            @php($dayField = $field.'_day')
            <div>
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $fieldLabel }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="time"
                    value="{{ old($field, substr($record->{$field} ?? '', 0, 5)) }}" @required($field !== 'check_out_end')
                    @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                    class="mt-1 min-h-12 w-full min-w-0 rounded-[10px] border border-control px-3 tabular-nums">
                @error($field)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
                @if ($field !== 'expected_check_in')
                    <label for="{{ $dayField }}" class="mt-2 block text-sm font-bold">{{ match ($field) {
                        'check_in_start' => 'Hari mulai absen masuk',
                        'expected_check_out' => 'Hari pulang',
                        'check_out_end' => 'Hari batas absen pulang',
                    } }}</label>
                    <select id="{{ $dayField }}" name="{{ $dayField }}" class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
                        @error($dayField) aria-invalid="true" aria-describedby="{{ $dayField }}-error" @enderror>
                        <option value="0" @selected((string) old($dayField, $record->{$dayField} ?? 0) === '0')>Hari yang sama</option>
                        @if ($field === 'check_in_start')
                            <option value="-1" @selected((string) old($dayField, $record->{$dayField}) === '-1')>Hari sebelumnya</option>
                        @else
                            <option value="1" @selected((string) old($dayField, $record->{$dayField}) === '1')>Hari berikutnya</option>
                        @endif
                    </select>
                    @error($dayField)
                        <p id="{{ $dayField }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                @endif
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
