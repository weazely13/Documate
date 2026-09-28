@php
    $tabs = [
        ['route' => 'admin.semesters', 'label' => 'Semesters & School Years', 'icon' => 'bx-calendar-alt'],
        ['route' => 'admin.clearance-monitoring', 'label' => 'Clearance Monitoring', 'icon' => 'bx-task'],
    ];
@endphp

<div class="inline-flex flex-wrap items-center gap-1 rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm">
    @foreach($tabs as $tab)
        @php $active = request()->routeIs($tab['route']); @endphp
        <a href="{{ route($tab['route']) }}" wire:navigate
            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition
                {{ $active
                    ? 'bg-[#2A57B4] text-white shadow-sm shadow-[#2A57B4]/30'
                    : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700' }}">
            <i class='bx {{ $tab['icon'] }} text-base'></i>
            {{ $tab['label'] }}
        </a>
    @endforeach
</div>