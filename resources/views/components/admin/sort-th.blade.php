{{-- resources/views/components/admin/sort-th.blade.php
     Sortable table header. Use inside a Livewire view that has sortBy($key).
     <x-admin.sort-th key="student" label="Student" :sort="$sortKey" :dir="$sortDir" /> --}}
@props(['key', 'label', 'sort' => null, 'dir' => null])

@php $active = $sort === $key; @endphp

<th scope="col" aria-sort="{{ $active ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none' }}"
    {{ $attributes->class(['px-4 py-3 text-left font-medium']) }}>
    <button type="button" wire:click="sortBy('{{ $key }}')"
            class="inline-flex items-center gap-1 rounded transition {{ $active ? 'text-[#2A57B4]' : 'text-slate-500 hover:text-slate-800' }}">
        {{ $label }}
        <svg class="h-3.5 w-3.5 {{ $active ? '' : 'opacity-40' }}" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            @if ($active && $dir === 'asc')
                <path d="M10 5l5 6H5l5-6Z"/>
            @elseif ($active)
                <path d="M10 15l-5-6h10l-5 6Z"/>
            @else
                <path d="M10 3l4 5H6l4-5Zm0 14l-4-5h8l-4 5Z"/>
            @endif
        </svg>
    </button>
</th>