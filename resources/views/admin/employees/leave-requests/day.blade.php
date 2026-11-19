@php
    $dateKey = 'days.'.$row.'.date';
    $durationKey = 'days.'.$row.'.duration';
@endphp
<div data-leave-day class="grid gap-3 sm:grid-cols-2">
    <div>
        <label for="date-{{ $row }}" class="block text-sm font-bold">Tanggal cuti</label>
        <input id="date-{{ $row }}" name="days[{{ $row }}][date]" type="date" min="0001-01-01" max="9999-12-31" required
            value="{{ is_string($day['date'] ?? null) ? $day['date'] : '' }}" class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
            @error($dateKey) aria-invalid="true" aria-describedby="date-{{ $row }}-error" @enderror>
        @error($dateKey)<p id="date-{{ $row }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="duration-{{ $row }}" class="block text-sm font-bold">Durasi (hari)</label>
        <input id="duration-{{ $row }}" name="days[{{ $row }}][duration]" type="number" min="0.01" max="1" step="0.01" required
            value="{{ is_scalar($day['duration'] ?? null) ? $day['duration'] : '1' }}" class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
            @error($durationKey) aria-invalid="true" aria-describedby="duration-{{ $row }}-error" @enderror>
        @error($durationKey)<p id="duration-{{ $row }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>@enderror
    </div>
    <button type="button" data-remove-leave-day hidden class="min-h-11 w-fit px-2 text-sm font-bold underline">Hapus tanggal</button>
</div>
