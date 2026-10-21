@php
    $tabs = [
        ['route' => 'admin.semesters', 'label' => 'Semesters & School Years', 'icon' => 'bx-calendar-alt'],
        ['route' => 'admin.clearance-monitoring', 'label' => 'Clearance Monitoring', 'icon' => 'bx-task'],
    ];
@endphp

<nav aria-label="Clearance sections" class="mb-6 flex gap-6 overflow-x-auto whitespace-nowrap border-b border-gray-200">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['route']) || request()->routeIs($tab['route'] . '.*'); @endphp
        <a href="{{ route($tab['route']) }}" wire:navigate
           @if ($active) aria-current="page" @endif
           class="-mb-px inline-flex items-center gap-2 border-b-2 px-1 pb-3 text-sm font-medium transition
               {{ $active
                   ? 'border-[#2A57B4] text-[#2A57B4]'
                   : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
            <i class='bx {{ $tab['icon'] }} text-base'></i>
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>