<?php

namespace App\Organization\Services;

use App\Organization\Models\JobDesk;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class JobDeskService
{
    public function paginate(): LengthAwarePaginator
    {
        return JobDesk::with('position:id,code,name')->orderBy('title')->orderBy('id')->paginate(15);
    }

    public function create(array $data): JobDesk
    {
        $record = new JobDesk;
        $this->update($data, $record);

        return $record;
    }

    public function update(array $data, JobDesk $record): void
    {
        try {
            $record->fill($data)->saveOrFail();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'title' => 'Judul ini sudah dipakai pada jabatan yang sama.',
            ]);
        }
    }

    public function delete(JobDesk $record): void
    {
        $record->delete();
    }
}
