<?php

namespace App\Organization\Services;

use App\Organization\Models\JobDesk;
use Illuminate\Pagination\LengthAwarePaginator;

class JobDeskService
{
    public function paginate(): LengthAwarePaginator
    {
        return JobDesk::with('position:id,code,name')->orderBy('title')->orderBy('id')->paginate(15);
    }

    public function create(array $data): JobDesk
    {
        return JobDesk::create($data);
    }

    public function update(array $data, JobDesk $record): void
    {
        $record->update($data);
    }

    public function delete(JobDesk $record): void
    {
        $record->delete();
    }
}
