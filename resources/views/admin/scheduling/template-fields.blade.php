@php
    $savedDays = $record->exists ? $record->days->keyBy('weekday_number') : collect();
    $notes = $formValue('description', $record->description);
@endphp

<div>
    <label for="description" class="block text-sm font-bold">Deskripsi (opsional)</label>
    <textarea id="description" name="description" maxlength="2000" rows="2"
        @error('description') aria-invalid="true" aria-describedby="description-error" @enderror
        class="mt-1 w-full rounded-[10px] border border-control px-3 py-2">{{ is_string($notes) ? $notes : '' }}</textarea>
    @error('description')<p id="description-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
</div>

<fieldset class="grid gap-3">
    <legend class="mb-1 text-sm font-bold">Pola Senin sampai Minggu</legend>
    <p id="weekday-hint" class="text-sm text-muted">Pilih shift atau Libur mingguan untuk tiap hari. Template aktif memerlukan shift dan waktu absen yang aktif.</p>
    @error('days')<p role="alert" class="text-sm text-[#b42318]">{{ $message }}</p>@enderror
    @foreach ($record::WEEKDAYS as $number => $weekday)
        @php($selectedShift = $formValue("days.$number", $savedDays->has($number) ? ($savedDays[$number]->work_shift_id ?? 0) : null))
        <div class="grid gap-1 sm:grid-cols-[6rem_minmax(0,1fr)] sm:items-center">
            <label for="day-{{ $number }}" class="text-sm font-bold">{{ $weekday }}</label>
            <select id="day-{{ $number }}" name="days[{{ $number }}]" required class="min-h-12 w-full rounded-[10px] border border-control bg-white px-3"
                aria-describedby="weekday-hint @error('days.'.$number) day-{{ $number }}-error @enderror"
                @error('days.'.$number) aria-invalid="true" @enderror>
                <option value="">Pilih shift atau libur</option>
                <option value="0" @selected($selectedShift === '0' || $selectedShift === 0)>Libur mingguan</option>
                @foreach ($shifts as $shift)
                    <option value="{{ $shift->id }}" @selected(is_scalar($selectedShift) && (string) $selectedShift === (string) $shift->id)>{{ $shift->name }} ({{ $shift->code }}){{ $shift->is_active && $shift->attendanceTime->is_active ? '' : ' · Nonaktif' }}</option>
                @endforeach
            </select>
            @error('days.'.$number)<p id="day-{{ $number }}-error" class="text-sm text-[#b42318] sm:col-start-2">{{ $message }}</p>@enderror
        </div>
    @endforeach
</fieldset>
