<div>
    <label for="position_id" class="block text-sm font-bold">Jabatan</label>
    <select id="position_id" name="position_id" required class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3"
        @error('position_id') aria-invalid="true" aria-describedby="position_id-error" @enderror>
        <option value="">Pilih jabatan</option>
        @foreach ($positions as $position)
            <option value="{{ $position->id }}" @selected((string) old('position_id', $record->position_id) === (string) $position->id)>
                {{ $position->name }} ({{ $position->code }}){{ $position->is_active ? '' : ' · Nonaktif' }}
            </option>
        @endforeach
    </select>
    @error('position_id')
        <p id="position_id-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="title" class="block text-sm font-bold">Judul job desk</label>
    <input id="title" name="title" value="{{ old('title', $record->title) }}" maxlength="150" required
        @error('title') aria-invalid="true" aria-describedby="title-error" @enderror
        class="mt-1 min-h-12 w-full rounded-[10px] border border-control px-3">
    @error('title')
        <p id="title-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
    @enderror
</div>

@foreach (['job_description' => 'Uraian tugas', 'job_specification' => 'Spesifikasi jabatan'] as $field => $fieldLabel)
    <div>
        <label for="{{ $field }}" class="block text-sm font-bold">{{ $fieldLabel }} (opsional)</label>
        <textarea id="{{ $field }}" name="{{ $field }}" rows="4" maxlength="10000"
            @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
            class="mt-1 w-full rounded-[10px] border border-control px-3 py-2">{{ old($field, $record->{$field}) }}</textarea>
        @error($field)
            <p id="{{ $field }}-error" class="mt-1 text-sm text-[#b42318]">{{ $message }}</p>
        @enderror
    </div>
@endforeach
