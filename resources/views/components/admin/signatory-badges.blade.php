@props(['user'])
@php
    $today = \Illuminate\Support\Carbon::today();
    $rows = $user->signatoryAssignments->filter(fn ($a) => $a->role)->map(function ($a) use ($today) {
        [$state, $cls] = match (true) {
            ! $a->is_active => ['Ended', 'bg-gray-100 text-gray-500'],
            $a->active_from && $a->active_from->gt($today) => ['Starts ' . $a->active_from->format('M j'), 'bg-blue-50 text-blue-600'],
            $a->active_until && $a->active_until->lt($today) => ['Expired', 'bg-amber-50 text-amber-700'],
            default => ['Active', 'bg-emerald-50 text-emerald-700'],
        };
        return ['a' => $a, 'state' => $state, 'cls' => $cls];
    })->sortBy(fn ($r) => $r['state'] === 'Active' ? 0 : 1);
@endphp

@if ($rows->isNotEmpty())
    <div {{ $attributes->class('flex flex-wrap gap-1') }}>
        @foreach ($rows as $r)
            <span class="inline-flex max-w-full items-center gap-1 truncate rounded-full px-2 py-0.5 text-[10px] font-medium {{ $r['cls'] }}"
                  title="{{ $r['a']->role->label }} · {{ $r['state'] }}{{ $r['a']->scope_name ? ' · ' . $r['a']->scope_name : '' }}">
                {{ $r['a']->role->label }}
                @if ($r['a']->scope_name)
                    <span class="opacity-70">· {{ \Illuminate\Support\Str::limit($r['a']->scope_name, 18) }}</span>
                @endif
            </span>
        @endforeach
    </div>
@endif