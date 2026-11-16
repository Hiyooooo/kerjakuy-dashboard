<div>
    <label for="holiday_date" class="block text-sm font-bold">Tanggal libur</label>
    <input id="holiday_date" name="holiday_date" type="date" min="0001-01-01" max="9999-12-31" required
        value="{{ old('holiday_date', $record->holiday_date?->toDateString()) }}"
        @error('holiday_date') aria-invalid="true" aria-describedby="holiday_date-error" @enderror
        class="mt-1 min-h-12 w-full min-w-0 rounded-[10px] border border-control px-3 tabular-nums">
    @error('holiday_date')
        <p id="holiday_date-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>

<div>
    @php
        $oldScope = old('office_id', $record->exists ? ($record->office_id ?? 'all') : '');
        $selectedScope = is_int($oldScope) || is_string($oldScope) ? (string) $oldScope : '';
    @endphp
    <label for="office_id" class="block text-sm font-bold">Berlaku untuk</label>
    <p id="holiday-scope-hint" class="mt-1 text-sm text-muted">Pilih semua kantor untuk libur perusahaan, atau pilih kantor yang libur.</p>
    <select id="office_id" name="office_id" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
        aria-describedby="holiday-scope-hint @error('office_id') office_id-error @enderror" @error('office_id') aria-invalid="true" @enderror>
        <option value="" disabled @selected($selectedScope !== 'all' && ! $offices->contains(fn ($office) => (string) $office->id === $selectedScope))>Pilih cakupan</option>
        <option value="all" @selected($selectedScope === 'all')>Semua kantor</option>
        @foreach ($offices as $office)
            <option value="{{ $office->id }}" @selected($selectedScope === (string) $office->id)>
                {{ $office->name }} ({{ $office->code }}){{ $office->is_active ? '' : ' · Nonaktif' }}
            </option>
        @endforeach
    </select>
    @error('office_id')
        <p id="office_id-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>
