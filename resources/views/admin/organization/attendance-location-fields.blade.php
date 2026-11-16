@if ($record->exists)
    @php($editVersion = session()->hasOldInput() ? old('edit_version') : $record->editVersion())
    <input name="edit_version" type="hidden" value="{{ is_string($editVersion) ? $editVersion : '' }}">
    @error('edit_version')
        <div class="text-sm text-[#b42318]">
            <p role="alert">{{ $message }}</p>
            <a href="{{ route('admin.attendance-locations.edit', $record) }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
        </div>
    @enderror
@endif

<div>
    @php($selectedOffice = old('office_id', $record->office_id))
    <label for="office_id" class="block text-sm font-bold">Kantor (opsional)</label>
    <select id="office_id" name="office_id" class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
        @error('office_id') aria-invalid="true" aria-describedby="office_id-error" @enderror>
        <option value="">Tanpa kantor</option>
        @foreach ($offices as $office)
            <option value="{{ $office->id }}" @selected(is_scalar($selectedOffice) && (string) $selectedOffice === (string) $office->id)>
                {{ $office->name }} ({{ $office->code }}){{ $office->is_active ? '' : ' · Nonaktif' }}
            </option>
        @endforeach
    </select>
    @error('office_id')
        <p id="office_id-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>

<fieldset class="grid gap-3">
    <legend class="mb-1 text-sm font-bold">Titik pusat lokasi absen</legend>
    <p id="attendance-coordinate-hint" class="text-sm text-muted">Salin lintang dan bujur titik absen dari aplikasi peta.</p>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach (['latitude' => ['Lintang', 90], 'longitude' => ['Bujur', 180]] as $field => [$label, $limit])
            @php($value = old($field, $record->{$field}))
            <div class="min-w-0">
                <label for="{{ $field }}" class="block text-sm font-bold">{{ $label }}</label>
                <input id="{{ $field }}" name="{{ $field }}" type="number" step="0.0000001" min="{{ -$limit }}" max="{{ $limit }}" required
                    value="{{ is_scalar($value) ? $value : '' }}"
                    aria-describedby="attendance-coordinate-hint @error($field) {{ $field }}-error @enderror"
                    @error($field) aria-invalid="true" @enderror
                    class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
                @error($field)
                    <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>
</fieldset>

<div>
    @php($radius = old('radius_meter', $record->radius_meter))
    @php($maxRadius = $record::MAX_RADIUS_METER)
    <label for="radius_meter" class="block text-sm font-bold">Radius (meter)</label>
    <p id="attendance-radius-hint" class="mt-1 text-sm text-muted">Jarak dari titik pusat lokasi absen, antara 1 dan {{ number_format($maxRadius, 0, ',', '.') }} meter.</p>
    <input id="radius_meter" name="radius_meter" type="number" step="1" min="1" max="{{ $maxRadius }}" required
        value="{{ is_scalar($radius) ? $radius : '' }}"
        aria-describedby="attendance-radius-hint @error('radius_meter') radius_meter-error @enderror"
        @error('radius_meter') aria-invalid="true" @enderror
        class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
    @error('radius_meter')
        <p id="radius_meter-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>
