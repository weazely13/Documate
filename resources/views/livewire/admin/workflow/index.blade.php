@php
    use App\Workflow\Documents\DocumentRegistry;

    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4] focus-visible:ring-offset-2';
    $field = 'rounded-lg border-slate-200 bg-white py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-2 sm:text-sm';

    // One plain-language status per row, tied to the tab it sits in: [text, text classes, icon + its colour].
    // Plain text, colour on the icon only; "ready" keeps green text because it is the one asking for action.
    $statusFor = function ($row) use ($tab) {
        return match (true) {
            $tab === 'action_required' => ['Waiting on student', 'text-slate-700', 'bx-hourglass text-[#c9531f]'],
            $tab === 'signing' => ['Signed ' . (int) $row->approved_count . ' of ' . (int) $row->approvals_count, 'text-slate-700', 'bx-pen text-slate-400'],
            $row->workflow_status?->value === 'approved' => ['Ready to finish', 'font-medium text-emerald-700', 'bx-check-circle text-emerald-600'],
            default => ['Needs review', 'text-slate-700', 'bx-time-five text-[#2A57B4]'],
        };
    };

    // Where the transaction is on its way: Review -> Signing -> Finish.
    // Returns [current step index, step labels, current step's tone: dot ring + text].
    $stageFor = function ($row) use ($tab) {
        $labels = ['Review', 'Signing', 'Finish'];

        return match (true) {
            $tab === 'action_required' => [0, ['With student', 'Signing', 'Finish'], ['ring-[#c9531f]', 'text-[#b4471a]']],
            $tab === 'signing' => [1, ['Review', 'Signed ' . (int) $row->approved_count . '/' . (int) $row->approvals_count, 'Finish'], ['ring-[#2A57B4]', 'text-[#1E4090]']],
            $row->workflow_status?->value === 'approved' => [2, ['Review', 'Signing', 'Ready to finish'], ['ring-emerald-600', 'text-emerald-700']],
            default => [0, $labels, ['ring-[#2A57B4]', 'text-[#1E4090]']],
        };
    };

    // The row's button says what opening it is for; ready rows get the solid one.
    $actionFor = fn ($row) => match (true) {
        $row->workflow_status?->value === 'approved' => ['Finish', 'bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]'],
        $tab === 'attention' => ['Review', 'text-[#2A57B4] hover:bg-[#2A57B4]/[0.07]'],
        default => ['View', 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'],
    };

    $nameFor = fn ($row) => $row->field_values['student_name']
        ?? (trim(($row->user?->first_name ?? '') . ' ' . ($row->user?->last_name ?? '')) ?: '-');

    $initialsFor = function ($row) use ($nameFor) {
        $parts = preg_split('/\s+/', trim($nameFor($row))) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last) ?: '?';
    };

    // The student's photo, laid over their initials; if it is missing or fails to load, the initials show.
    $avatarFor = fn ($row) => $row->user?->profile_picture ? asset('storage/' . $row->user->profile_picture) : null;

    $metaFor = fn ($row) => collect([$row->transaction_code, $row->user?->student_number])->filter()->implode(' · ');

    $oldest = $overdue->first();
    $oldestLabel = $oldest
        ? ($oldest->field_values['student_name'] ?? $oldest->user?->first_name) . "'s " . (DocumentRegistry::find($oldest->document_type)?->name() ?? $oldest->document_type)
        : null;

    // Priority lives in the Waiting cell alone, not across the whole row: the legend's dot colours,
    // one calm type step per level, and a soft chip only once it is overdue (pulled left so dots stay aligned).
    $waitStyle = fn ($row) => match ($row->waiting['level']) {
        'overdue' => '-ml-2 rounded-md bg-red-50 px-2 py-0.5 font-semibold text-red-800',
        'urgent' => 'font-medium text-red-700',
        'follow_up' => 'font-medium text-orange-700',
        'due' => 'text-slate-700',
        default => 'text-slate-500',
    };

    // Tab tiles, in brand colours: [tab, solid background, icon].
    $tabTiles = [
        ['attention', 'bg-[#2A57B4]', 'bx-time-five'],
        ['signing', 'bg-[#1E4090]', 'bx-pen'],
        ['action_required', 'bg-[#b4471a]', 'bx-hourglass'],
    ];

    // Phones: queue names that fit a third of a 320px screen; "With student" matches the card tracker.
    $shortTabs = ['action_required' => 'With student'];

    $legend = [
        ['bg-slate-300', 'Under 1'],
        ['bg-amber-300', '1–2'],
        ['bg-orange-500', '3–4 Follow up'],
        ['bg-red-600', '5–6 Urgent'],
        ['bg-red-900', '7+ Overdue'],
    ];
@endphp

<div class="cq mx-auto max-w-6xl space-y-4 md:space-y-5">

    {{-- Controls. Phones: sticky search row + queue switcher, so status stays in reach while scrolling. md+: one bar. --}}
    <div class="sticky -top-4 z-20 -mx-4 -mt-4 space-y-3 border-b border-slate-200/80 bg-white px-4 pb-3 pt-3 md:static md:mx-0 md:mt-0 md:space-y-0 md:border-0 md:bg-transparent md:p-0">
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
            <p class="hidden text-sm text-slate-500 md:block">
                Longest waiting first. Finished ones are in
                <a href="{{ route('admin.transactions.index') }}" wire:navigate class="font-medium text-[#2A57B4] hover:underline">Transaction Records</a>.
            </p>

            <div class="flex items-center gap-2">
                <div class="relative min-w-0 flex-1 xl:w-72 xl:flex-none">
                    <i class='bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400' aria-hidden="true"></i>
                    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Name, code or document"
                           aria-label="Search by name, student number, code or document type" class="w-full pl-9 pr-3 max-md:h-11 {{ $field }}">
                </div>

                {{-- Phones: an icon that opens the native picker; md+: the full menu --}}
                <div class="relative shrink-0">
                    <label for="sort-menu" class="sr-only">Sort by</label>
                    <select id="sort-menu" wire:model.live="sort"
                            class="max-md:h-11 max-md:w-11 max-md:bg-none max-md:px-0 max-md:text-transparent md:w-48 {{ $field }}">
                        @foreach ($sortOptions as $value => $text)
                            <option value="{{ $value }}" class="text-slate-900">{{ $text }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute inset-0 flex items-center justify-center text-xl text-slate-600 md:hidden" aria-hidden="true">
                        <i class='bx bx-sort-alt-2'></i>
                        @if ($sort !== '')
                            <span class="absolute right-2 top-2 h-2 w-2 rounded-full bg-[#eb6834]"></span>
                        @endif
                    </span>
                </div>

                <a href="{{ route('admin.workflow.issue') }}" wire:navigate
                   class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg bg-[#2A57B4] px-3 text-sm font-semibold text-white shadow-sm hover:bg-[#24499A] max-md:h-11 md:gap-1.5 md:px-4 md:py-2 {{ $focus }}">
                    <i class='bx bx-plus text-base' aria-hidden="true"></i>
                    <span class="md:hidden">Issue</span>
                    <span class="hidden md:inline">Issue a document</span>
                </a>
            </div>
        </div>

        {{-- Phones: queue switcher --}}
        <nav class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 p-1 md:hidden" aria-label="Queue">
            @foreach ($tabs as $key => $label)
                @php $late = $lateCounts[$key] ?? 0; $current = $tab === $key; @endphp
                <button type="button" wire:click="setTab('{{ $key }}')" wire:key="seg-{{ $key }}"
                        @if ($current) aria-current="page" @endif
                        class="flex min-h-[54px] min-w-0 flex-col items-center justify-center rounded-lg px-1 text-center transition {{ $focus }}
                            {{ $current ? 'bg-[#2A57B4] text-white shadow-[0_4px_10px_-4px_rgba(30,64,144,0.7)]' : 'text-slate-700 active:bg-white/70' }}">
                    <span class="w-full truncate text-[12px] font-medium leading-tight {{ $current ? 'text-white/90' : 'text-slate-600' }}">{{ $shortTabs[$key] ?? $label }}</span>
                    <span class="mt-0.5 text-base font-bold tabular-nums leading-tight">
                        {{ $counts[$key] }}@if ($late)<span class="ml-1 text-[11px] font-semibold {{ $current ? 'text-white/85' : 'text-red-700' }}">· {{ $late }} late</span>@endif
                    </span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Phones: the two at-a-glance numbers as chips --}}
    @if ($readyCount > 0 || $overdue->isNotEmpty())
        <div class="flex flex-wrap gap-2 md:hidden">
            @if ($readyCount > 0)
                <button type="button" wire:click="setTab('attention')"
                        class="inline-flex min-h-[40px] items-center gap-1.5 rounded-full bg-emerald-50 px-3 text-[13px] font-semibold text-emerald-800 active:bg-emerald-100 {{ $focus }}">
                    <i class='bx bx-check-circle text-base' aria-hidden="true"></i>
                    {{ $readyCount }} ready to finish
                </button>
            @endif
            @if ($overdue->isNotEmpty())
                <span role="alert" class="inline-flex min-h-[40px] items-center gap-1.5 rounded-full bg-red-50 px-3 text-[13px] font-semibold text-red-800" title="Oldest: {{ $oldestLabel }}">
                    <i class='bx bx-error text-base' aria-hidden="true"></i>
                    {{ $overdue->count() }} overdue
                </span>
            @endif
        </div>
    @endif

    {{-- Count tiles (md+): the three queues are tabs; ready and overdue are at-a-glance --}}
    <div class="hidden gap-3 md:grid md:grid-cols-3 xl:grid-cols-5">
        @foreach ($tabTiles as [$key, $bg, $icon])
            @php $late = $lateCounts[$key] ?? 0; $current = $tab === $key; @endphp
            <button type="button" wire:click="setTab('{{ $key }}')" wire:key="tile-{{ $key }}"
                    @if ($current) aria-current="page" @endif
                    class="group relative flex min-h-[96px] flex-col justify-between rounded-2xl p-4 text-left text-white transition {{ $bg }} {{ $focus }}
                        {{ $current ? 'ring-2 ring-offset-2 ring-[#2A57B4]/60 shadow-[0_10px_24px_-14px_rgba(30,64,144,0.9)]' : 'opacity-[0.88] hover:opacity-100' }}">
                <span class="flex items-center gap-1.5 text-[13px] font-medium text-white/90">
                    <i class='bx {{ $icon }} text-base' aria-hidden="true"></i>
                    {{ $tabs[$key] }}
                </span>
                <span class="flex items-end justify-between gap-2">
                    <span class="text-3xl font-bold tabular-nums leading-none">{{ $counts[$key] }}</span>
                    @if ($late)
                        <span class="rounded-md bg-white/20 px-1.5 py-0.5 text-[11px] font-semibold">{{ $late }} late</span>
                    @elseif ($current)
                        <span class="text-[11px] font-semibold text-white/80">Showing</span>
                    @endif
                </span>
            </button>
        @endforeach

        <button type="button" wire:click="setTab('attention')" title="Ready rows are in To review"
                class="relative flex min-h-[96px] flex-col justify-between rounded-2xl bg-emerald-700 p-4 text-left text-white opacity-[0.92] transition hover:opacity-100 {{ $focus }}">
            <span class="flex items-center gap-1.5 text-[13px] font-medium text-white/90">
                <i class='bx bx-check-circle text-base' aria-hidden="true"></i>
                Ready to finish
            </span>
            <span class="flex items-end justify-between gap-2">
                <span class="text-3xl font-bold tabular-nums leading-none">{{ $readyCount }}</span>
                <span class="text-[11px] font-semibold text-white/90">Everyone signed</span>
            </span>
        </button>

        <div role="{{ $overdue->isNotEmpty() ? 'alert' : 'status' }}"
             class="flex min-h-[96px] flex-col justify-between rounded-2xl p-4 {{ $overdue->isNotEmpty() ? 'bg-red-700 text-white' : 'border border-slate-200 bg-white text-slate-700' }}">
            <span class="flex items-center gap-1.5 text-[13px] font-medium {{ $overdue->isNotEmpty() ? 'text-white/90' : 'text-slate-500' }}">
                <i class='bx bx-error text-base' aria-hidden="true"></i>
                Overdue (7+ days)
            </span>
            <span>
                <span class="text-3xl font-bold tabular-nums leading-none">{{ $overdue->count() }}</span>
                @if ($oldest)
                    <span class="mt-1 block truncate text-[11px] font-medium text-white/90" title="Oldest: {{ $oldestLabel }}">
                        Oldest: {{ $oldest->field_values['student_name'] ?? $oldest->user?->first_name }}
                    </span>
                @endif
            </span>
        </div>
    </div>

    {{-- Waiting-time legend (only where it is flagged). Phones: folded away until asked for. --}}
    @if (in_array($tab, \App\Livewire\Admin\Workflow\Index::FLAGGED_TABS, true))
        <details class="group rounded-xl border border-slate-200/80 bg-white px-3.5 md:hidden">
            <summary class="flex min-h-[44px] cursor-pointer list-none items-center justify-between text-sm font-medium text-slate-700 [&::-webkit-details-marker]:hidden">
                What the waiting colours mean
                <i class='bx bx-chevron-down text-lg text-slate-400 transition group-open:rotate-180' aria-hidden="true"></i>
            </summary>
            <p class="pb-1 text-xs text-slate-500">Counted in school days.</p>
            <ul class="grid grid-cols-2 gap-x-4 gap-y-2 pb-3.5 text-[13px] text-slate-700">
                @foreach ($legend as [$dot, $text])
                    <li class="flex items-center gap-2"><span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}"></span>{{ $text }}</li>
                @endforeach
            </ul>
        </details>

        <div class="hidden flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-600 md:flex">
            <span class="font-semibold">Waiting (school days)</span>
            @foreach ($legend as [$dot, $text])
                <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full {{ $dot }}"></span>{{ $text }}</span>
            @endforeach
        </div>
    @endif

    {{-- Table, once the page itself is wide enough for all five columns (not the screen: the sidebar takes a share on tablets) --}}
    <div class="hidden transition-opacity cq-lg:block" wire:loading.class="opacity-50" wire:loading.attr="aria-busy"
         wire:target="search, sort, setTab, sortBy, gotoPage, nextPage, previousPage">
        <table class="min-w-full border-separate border-spacing-y-1.5 text-sm">
            <thead class="text-xs">
                <tr>
                    <x-admin.sort-th key="student" label="Student" :sort="$sortKey" :dir="$sortDir" class="pl-5" />
                    <th scope="col" class="px-4 py-2 text-left font-medium text-slate-500">Document</th>
                    <x-admin.sort-th key="status" label="Status" :sort="$sortKey" :dir="$sortDir" />
                    <x-admin.sort-th key="waiting" label="Waiting" :sort="$sortKey" :dir="$sortDir" />
                    <th class="w-28"><span class="sr-only">Action</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    @php
                        $doc = DocumentRegistry::find($row->document_type);
                        [$statusText, $statusClass, $statusIcon] = $statusFor($row);
                        [$actionText, $actionClass] = $actionFor($row);
                        $url = route('admin.workflow.show', $row->workspace_id);
                        $cell = 'border-y border-slate-200/70 bg-white py-3.5 transition-colors group-hover:bg-slate-50/80';
                    @endphp
                    <tr wire:key="row-{{ $row->workspace_id }}" class="group cursor-pointer"
                        onclick="Livewire.navigate('{{ $url }}')">
                        <td class="{{ $cell }} rounded-l-xl border-l pl-5 pr-4">
                            <div class="flex items-center gap-3">
                                <span class="relative grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full bg-[#2A57B4]/10 text-xs font-bold text-[#2A57B4]" aria-hidden="true">
                                    {{ $initialsFor($row) }}
                                    @if ($avatarFor($row))
                                        <img src="{{ $avatarFor($row) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                                    @endif
                                </span>
                                <div class="min-w-0">
                                    <a href="{{ $url }}" wire:navigate onclick="event.stopPropagation()"
                                       class="block max-w-[240px] truncate rounded font-semibold text-slate-900 hover:text-[#2A57B4] {{ $focus }}">
                                        {{ $nameFor($row) }}
                                    </a>
                                    @if ($metaFor($row) !== '')
                                        <p class="mt-0.5 truncate text-xs tabular-nums text-slate-500">{{ $metaFor($row) }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="{{ $cell }} px-4">
                            <p class="max-w-[260px] truncate text-slate-700" title="{{ $doc?->name() ?? $row->document_type }}">
                                {{ $doc?->name() ?? $row->document_type }}
                            </p>
                            @if ($row->issuer)
                                <p class="mt-0.5 max-w-[260px] truncate text-xs text-slate-500">Issued by <span class="text-slate-700">{{ $row->issuer }}</span></p>
                            @endif
                        </td>
                        <td class="{{ $cell }} px-4">
                            <span class="inline-flex items-center gap-1.5 whitespace-nowrap text-[13px] {{ $statusClass }}">
                                <i class='bx {{ $statusIcon }} text-base' aria-hidden="true"></i>
                                {{ $statusText }}
                            </span>
                        </td>
                        <td class="{{ $cell }} whitespace-nowrap px-4">
                            <span class="inline-flex items-center gap-2 {{ $waitStyle($row) }}">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $row->waiting['dot'] }}" aria-hidden="true"></span>
                                {{ $row->waiting['text'] }}
                            </span>
                        </td>
                        <td class="{{ $cell }} rounded-r-xl border-r pl-2 pr-4 text-right">
                            <a href="{{ $url }}" wire:navigate onclick="event.stopPropagation()"
                               class="inline-flex min-w-[76px] items-center justify-center rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $actionClass }} {{ $focus }}">
                                {{ $actionText }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center text-sm text-slate-500">
                            {{ trim($search) !== '' ? 'No match. Try another name or clear the search.' : 'Nothing waiting here.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Cards (phones and narrow pages): who, what, where it is on its way, and how long it has waited --}}
    <ul class="grid gap-3 transition-opacity cq-sm:grid-cols-2 cq-lg:hidden" aria-label="{{ $tabs[$tab] }}"
        wire:loading.class="opacity-50" wire:loading.attr="aria-busy"
        wire:target="search, sort, setTab, sortBy, gotoPage, nextPage, previousPage">
        @forelse ($rows as $row)
            @php
                $doc = DocumentRegistry::find($row->document_type);
                [$actionText] = $actionFor($row);
                [$stage, $stageLabels, [$stageRing, $stageText]] = $stageFor($row);
            @endphp
            <li wire:key="card-{{ $row->workspace_id }}">
                <a href="{{ route('admin.workflow.show', $row->workspace_id) }}" wire:navigate
                   class="flex h-full flex-col rounded-2xl border border-slate-200/70 bg-white p-4 transition-colors active:bg-slate-50 {{ $focus }}">

                    <div class="flex items-start gap-3">
                        <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#2A57B4]/10 text-[13px] font-bold text-[#2A57B4]" aria-hidden="true">
                            {{ $initialsFor($row) }}
                            @if ($avatarFor($row))
                                <img src="{{ $avatarFor($row) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[15px] font-semibold leading-snug text-slate-900">{{ $nameFor($row) }}</p>
                            @if ($metaFor($row) !== '')
                                <p class="mt-0.5 truncate text-xs tabular-nums text-slate-500">{{ $metaFor($row) }}</p>
                            @endif
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-0.5 rounded-full py-1 text-xs font-semibold
                            {{ $actionText === 'Finish' ? 'bg-[#2A57B4] pl-3 pr-2 text-white' : ($actionText === 'Review' ? 'text-[#2A57B4]' : 'text-slate-500') }}">
                            {{ $actionText }}
                            <i class='bx bx-chevron-right text-base' aria-hidden="true"></i>
                        </span>
                    </div>

                    <p class="mt-3 text-sm leading-snug text-slate-700">{{ $doc?->name() ?? $row->document_type }}</p>
                    @if ($row->issuer)
                        <p class="mt-0.5 text-xs text-slate-500">Issued by <span class="text-slate-700">{{ $row->issuer }}</span></p>
                    @endif

                    {{-- Progress: Review -> Signing -> Finish --}}
                    <ol class="mt-4 grid grid-cols-3" aria-label="Progress">
                        @foreach ($stageLabels as $i => $label)
                            @php $state = $i < $stage ? 'done' : ($i === $stage ? 'current' : 'todo'); @endphp
                            <li class="relative flex flex-col items-center text-center" @if ($state === 'current') aria-current="step" @endif>
                                @if ($i > 0)
                                    <span class="absolute right-1/2 top-[7px] h-0.5 w-full {{ $i <= $stage ? 'bg-[#2A57B4]' : 'bg-slate-200' }}" aria-hidden="true"></span>
                                @endif
                                <span class="relative z-10 grid h-4 w-4 place-items-center rounded-full
                                    {{ $state === 'done' ? 'bg-[#2A57B4] text-white' : ($state === 'current' ? 'bg-white ring-[3px] ' . $stageRing : 'bg-white ring-2 ring-slate-200') }}" aria-hidden="true">
                                    @if ($state === 'done')
                                        <i class='bx bx-check text-[12px] leading-none'></i>
                                    @endif
                                </span>
                                <span class="mt-1.5 px-0.5 text-[11px] leading-tight
                                    {{ $state === 'current' ? 'font-semibold ' . $stageText : ($state === 'done' ? 'text-slate-600' : 'text-slate-500') }}">
                                    <span class="sr-only">{{ $state === 'done' ? 'Done: ' : ($state === 'current' ? 'Now: ' : 'Next: ') }}</span>{{ $label }}
                                </span>
                            </li>
                        @endforeach
                    </ol>

                    <div class="h-4 shrink-0" aria-hidden="true"></div>
                    <div class="mt-auto flex items-center justify-between gap-3 border-t border-slate-900/[0.06] pt-3">
                        <span class="inline-flex items-center gap-2 text-sm {{ $waitStyle($row) }}">
                            <span class="h-2 w-2 shrink-0 rounded-full {{ $row->waiting['dot'] }}" aria-hidden="true"></span>
                            {{ $row->waiting['text'] }}
                        </span>
                    </div>
                </a>
            </li>
        @empty
            <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500 cq-sm:col-span-2">
                {{ trim($search) !== '' ? 'No match. Try another name or clear the search.' : 'Nothing waiting here.' }}
            </li>
        @endforelse
    </ul>

    @if ($rows->hasPages())
        <div>{{ $rows->links() }}</div>
    @endif
</div>
