{{-- One headline figure: label and icon, the value with an optional status pill, and a caption.
     @include('livewire.partials.dash.kpi', [
         'label' => 'To review', 'icon' => 'bx-task', 'value' => 14, 'url' => route(...),
         'pill' => '3 overdue', 'tone' => 'red',   // optional; red | amber | green | blue
         'caption' => 'Waiting on the office',      // optional
         'muted' => false,                          // true greys the value (nothing there)
         'valueClass' => 'text-emerald-600',         // optional colour for a status word
     ]) --}}
@php
    $pillTone = [
        'red' => 'bg-rose-50 text-rose-700',
        'amber' => 'bg-amber-50 text-amber-800',
        'green' => 'bg-emerald-50 text-emerald-700',
        'blue' => 'bg-blue-50 text-[#1E4090]',
    ][$tone ?? 'blue'];
@endphp

<a href="{{ $url }}" wire:navigate
   class="flex min-w-0 flex-col rounded-2xl border border-gray-200/70 bg-white p-4 transition cq-sm:p-5 hover:border-[#2A57B4]/30 hover:shadow-[0_8px_24px_-16px_rgba(13,37,61,0.35)] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]">
    <div class="flex items-start justify-between gap-2 cq-sm:items-center cq-sm:gap-3">
        <p class="line-clamp-2 text-sm font-medium leading-snug text-gray-700 cq-sm:block cq-sm:truncate">{{ $label }}</p>
        <i class='bx {{ $icon }} shrink-0 text-lg text-[#2A57B4]' aria-hidden="true"></i>
    </div>
    <div class="mt-3 flex flex-wrap items-center gap-x-2.5 gap-y-1">
        <p class="text-[1.75rem] font-bold leading-none tracking-tight tabular-nums cq-sm:text-3xl {{ ($muted ?? false) ? 'text-gray-300' : ($valueClass ?? 'text-gray-900') }}">{{ $value }}</p>
        @if (! empty($pill))
            <span class="rounded-md px-1.5 py-0.5 text-xs font-semibold {{ $pillTone }}">{{ $pill }}</span>
        @endif
    </div>
    @if (! empty($caption))
        <p class="mt-2 line-clamp-2 text-xs leading-snug text-gray-500 cq-sm:block cq-sm:truncate">{{ $caption }}</p>
    @endif
</a>
