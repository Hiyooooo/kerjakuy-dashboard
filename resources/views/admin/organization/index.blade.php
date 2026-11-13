@extends('layouts.admin')

@section('title', $label)

@section('content')
    <div data-enter class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="font-heading text-[40px] leading-tight font-extrabold tracking-tight">{{ $label }}</h1>
        <a href="{{ route('admin.'.$resource.'.create') }}" class="inline-flex min-h-12 items-center rounded-[10px] bg-coral px-4 font-bold hover:bg-coral/85 motion-safe:transition-colors">
            Tambah {{ Str::lower($label) }}
        </a>
    </div>

    @if (session('organization_status'))
        <p role="status" class="mt-5 rounded-[10px] border border-line bg-white px-4 py-3 text-[#26734d]">{{ session('organization_status') }}</p>
    @endif

    @if ($records->isEmpty())
        <p class="mt-6 rounded-[22px] border border-line bg-white p-6 text-muted">Belum ada {{ Str::lower($label) }} di halaman ini.</p>
    @else
        <div class="mt-6 overflow-hidden rounded-[22px] border border-line bg-white">
            <table class="w-full table-fixed text-left text-sm">
                <thead class="border-b border-line bg-paper">
                    <tr>
                        <th scope="col" class="px-3 py-3 sm:px-5">{{ $label }}</th>
                        <th scope="col" class="w-23 px-2 py-3 sm:w-32 sm:px-5">Status</th>
                        <th scope="col" class="w-16 px-2 py-3 text-right sm:w-24 sm:px-5">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($records as $record)
                        <tr class="focus-within:bg-paper/60 hover:bg-paper/60">
                            <th scope="row" class="px-3 py-3 font-bold wrap-anywhere sm:px-5">
                                {{ $record->name }}
                                <span class="mt-0.5 block text-xs font-semibold text-muted">{{ $record->code }}</span>
                                @if ($record->{$noteField})
                                    <span class="mt-1 block font-normal text-muted">{{ Str::limit($record->{$noteField}, 120) }}</span>
                                @endif
                            </th>
                            <td class="px-2 py-3 sm:px-5">
                                <span @class([
                                    'inline-flex items-center gap-1.5 rounded-[14px] px-2 py-1 text-xs font-bold',
                                    'bg-[#e9f5ee] text-[#26734d]' => $record->is_active,
                                    'bg-paper text-muted' => ! $record->is_active,
                                ])>
                                    <span aria-hidden="true" @class(['size-2 shrink-0 rounded-full border border-current', 'bg-current' => $record->is_active])></span>
                                    {{ $record->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-2 py-3 text-right sm:px-5">
                                <a href="{{ route('admin.'.$resource.'.edit', $record) }}" aria-label="Edit {{ $record->name }}" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-[10px] px-2 font-bold underline decoration-control/50 underline-offset-4 hover:bg-paper">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($records->hasPages())
        <div class="mt-5">{{ $records->links() }}</div>
    @endif
@endsection
