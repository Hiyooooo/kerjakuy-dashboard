<div>
    <label for="name" class="block text-sm font-bold">Nama waktu absen</label>
    <input id="name" name="name" value="{{ old('name', $record->name) }}" maxlength="100" required
        @error('name') aria-invalid="true" aria-describedby="name-error" @enderror
        class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
    @error('name')
        <p id="name-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>

@foreach ([
    'Jam kerja' => [
        'expected_check_in' => 'Jam masuk',
        'expected_check_out' => 'Jam pulang',
    ],
    'Jendela absen' => [
        'check_in_start' => 'Mulai absen masuk',
        'check_out_end' => 'Batas absen pulang (opsional)',
    ],
] as $group => $fields)
<fieldset @if ($loop->first) aria-describedby="attendance-time-hint" @endif class="grid min-w-0 gap-3 border-t border-line pt-2">
    <legend class="pr-2 font-heading font-extrabold">{{ $group }}</legend>
    @if ($loop->first)
        <p id="attendance-time-hint" class="text-sm text-muted">Pilihan hari dihitung dari tanggal jam masuk. Contoh shift malam: masuk 22.00, pulang 06.00, pilih Hari berikutnya.</p>
    @endif
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($fields as $field => $fieldLabel)
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
                    <select id="{{ $dayField }}" name="{{ $dayField }}" class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3 disabled:cursor-not-allowed disabled:bg-paper disabled:text-muted"
                        @if ($field === 'check_out_end' || $errors->has($dayField))
                            aria-describedby="{{ $field === 'check_out_end' ? 'check-out-end-day-hint' : '' }} @error($dayField) {{ $dayField }}-error @enderror"
                        @endif
                        @error($dayField) aria-invalid="true" @enderror>
                        <option value="0" @selected((string) old($dayField, $record->{$dayField} ?? 0) === '0')>Hari yang sama</option>
                        @if ($field === 'check_in_start')
                            <option value="-1" @selected((string) old($dayField, $record->{$dayField}) === '-1')>Hari sebelumnya</option>
                        @else
                            <option value="1" @selected((string) old($dayField, $record->{$dayField}) === '1')>Hari berikutnya</option>
                        @endif
                    </select>
                    @if ($field === 'check_out_end')
                        <p id="check-out-end-day-hint" class="mt-1 text-sm text-muted">Isi jam batas absen pulang untuk memilih hari.</p>
                    @endif
                    @error($dayField)
                        <p id="{{ $dayField }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                    @enderror
                @endif
            </div>
        @endforeach
    </div>
</fieldset>
@endforeach

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
