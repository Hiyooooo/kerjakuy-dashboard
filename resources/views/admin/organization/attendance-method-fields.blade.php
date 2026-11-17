@if ($record->exists)
    @php($editVersion = session()->hasOldInput() ? old('edit_version') : $record->editVersion())
    <input name="edit_version" type="hidden" value="{{ is_string($editVersion) ? $editVersion : '' }}">
    @error('edit_version')
        <div class="text-sm text-[#b42318]">
            <p role="alert">{{ $message }}</p>
            <a href="{{ route('admin.attendance-methods.edit', $record) }}" class="inline-flex min-h-11 items-center font-bold underline">Buka versi terbaru</a>
        </div>
    @enderror
@endif

<fieldset aria-describedby="attendance-requirements-hint" class="grid gap-2">
    <legend class="mb-1 text-sm font-bold">Syarat absen</legend>
    <p id="attendance-requirements-hint" class="text-sm text-muted">Pilih syarat yang wajib dipenuhi pegawai saat absen. Jika semua kotak kosong, metode ini tidak mensyaratkan lokasi, verifikasi wajah, atau perangkat terdaftar.</p>

    @foreach ($requirements as $field => $requirement)
        <input name="{{ $field }}" type="hidden" value="0">
        <label for="{{ $field }}" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-[10px] border border-line px-3 text-sm font-bold hover:bg-paper">
            <input id="{{ $field }}" name="{{ $field }}" type="checkbox" value="1"
                @checked(in_array(old($field, $record->{$field}), [true, 1, '1'], true))
                @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                class="size-5 accent-coral">
            {{ $requirement }}
        </label>
        @error($field)
            <p id="{{ $field }}-error" class="text-sm text-[#b42318]">{{ $message }}</p>
        @enderror
    @endforeach
</fieldset>
