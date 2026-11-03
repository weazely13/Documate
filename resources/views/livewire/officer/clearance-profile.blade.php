<div class="space-y-4 sm:space-y-6">
    <a href="{{ route($backRoute) }}" wire:navigate class="inline-flex items-center gap-1.5 py-1 text-sm font-medium text-slate-500 hover:text-[#2A57B4]">
        <i class='bx bx-arrow-back text-lg'></i> Back to list
    </a>

    @php
        $initials = strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'T', 0, 1));
        $statusTheme = fn (string $status) => match ($status) {
            'Cleared' => [
                'badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'border' => 'border-emerald-200/70',
                'bg' => 'bg-emerald-50/30',
                'icon' => 'bx-check-shield',
                'iconColor' => 'text-emerald-600',
            ],
            'Pending' => [
                'badge' => 'bg-amber-50 text-amber-700 border-amber-200',
                'border' => 'border-amber-200/70',
                'bg' => 'bg-amber-50/30',
                'icon' => 'bx-time-five',
                'iconColor' => 'text-amber-600',
            ],
            'Uncleared' => [
                'badge' => 'bg-rose-50 text-rose-700 border-rose-200',
                'border' => 'border-rose-200/70',
                'bg' => 'bg-rose-50/30',
                'icon' => 'bx-x-circle',
                'iconColor' => 'text-rose-600',
            ],
            default => [
                'badge' => 'bg-slate-100 text-slate-500 border-slate-200',
                'border' => 'border-slate-200',
                'bg' => 'bg-slate-50/30',
                'icon' => 'bx-help-circle',
                'iconColor' => 'text-slate-400',
            ],
        };
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-4 lg:items-start">

        {{-- LEFT: STUDENT INFORMATION --}}
        {{-- Mobile: stacked. Tablet (sm): 2 columns. Desktop (lg): single sidebar column. --}}
        <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 lg:col-span-1 lg:grid-cols-1">

            {{-- IDENTITY CARD --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-[#2A57B4] px-4 py-5 text-white sm:px-6 lg:py-8">
                    {{-- Mobile: avatar left, text right. Desktop: centered stack. --}}
                    <div class="flex items-center gap-4 lg:flex-col lg:gap-0 lg:text-center">
                        @if($user->profile_picture)
                            <img alt="Profile photo" src="{{ asset('storage/' . $user->profile_picture) }}" class="h-16 w-16 shrink-0 rounded-full object-cover ring-4 ring-white/25 lg:mx-auto lg:h-20 lg:w-20">
                        @else
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white/15 text-xl font-bold ring-4 ring-white/25 lg:mx-auto lg:h-20 lg:w-20 lg:text-2xl">
                                {{ $initials }}
                            </div>
                        @endif
                        <div class="min-w-0 lg:mt-4">
                            <h1 class="break-words text-base font-bold leading-tight sm:text-lg">{{ $fullName }}</h1>
                            <p class="mt-0.5 text-xs text-white/80">{{ $user->student_number ?: 'No student number' }}</p>
                            <p class="text-xs text-white/70">{{ $user->role->role_name ?? 'Student' }}</p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2 lg:justify-center">
                        <span class="max-w-full truncate rounded-full bg-white/15 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide sm:max-w-[180px]" title="{{ $user->organization?->name ?: 'Unassigned' }}">
                            {{ $user->organization?->name ?: 'Unassigned' }}
                        </span>
                        <span class="rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-wide {{ ($user->account_status ?? '') === 'active' ? 'bg-emerald-400/20 text-emerald-50' : 'bg-rose-400/20 text-rose-50' }}">
                            {{ ucfirst(str_replace('_', ' ', $user->account_status ?? 'inactive')) }}
                        </span>
                    </div>
                </div>

                <dl class="space-y-3 px-4 py-4 text-sm sm:px-6 sm:py-5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#2A57B4]">Personal Information</p>
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-slate-500">Sex</dt>
                        <dd class="min-w-0 truncate font-medium text-slate-800" title="{{ $user->sex ?: '-' }}">{{ $user->sex ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-slate-500">Birth Date</dt>
                        <dd class="min-w-0 truncate font-medium text-slate-800" title="{{ $user->date_of_birth ?: '-' }}">{{ $user->date_of_birth ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-slate-500">Contact No.</dt>
                        <dd class="min-w-0 truncate font-medium text-slate-800" title="{{ $user->contact_number ?: '-' }}">{{ $user->contact_number ?: '-' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- ACADEMIC INFO CARD --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                <p class="mb-4 text-[10px] font-bold uppercase tracking-wider text-[#2A57B4]">Academic Information</p>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-slate-500">College</dt>
                        <dd class="min-w-0 truncate text-right font-medium text-slate-800" title="{{ $user->college ?: '-' }}">{{ $user->college ?: '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-slate-500">Program</dt>
                        <dd class="min-w-0 truncate text-right font-medium text-slate-800" title="{{ $programLabel }}">{{ $programLabel }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-slate-500">Year &amp; Section</dt>
                        <dd class="min-w-0 truncate text-right font-medium text-slate-800" title="{{ $yearLevelLabel }}{{ $user->section ? ' - ' . $user->section : '' }}">{{ $yearLevelLabel }}{{ $user->section ? ' - ' . $user->section : '' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="shrink-0 text-slate-500">Academic Status</dt>
                        <dd class="min-w-0 truncate text-right font-medium text-slate-800" title="{{ $user->academic_status ?: '-' }}">{{ $user->academic_status ?: '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- RIGHT: TAGGING + HISTORY --}}
        <div class="min-w-0 space-y-4 sm:space-y-6 lg:col-span-3">

            {{-- Tag clearance: the page's main action, so it is the highlighted card and the status buttons lead --}}
            <section class="overflow-hidden rounded-xl border border-[#2A57B4]/30 bg-[#f0f4fb] shadow-sm ring-1 ring-[#2A57B4]/10" aria-labelledby="tag-title">
                <div class="flex items-center gap-3 border-b border-[#2A57B4]/15 px-4 py-3 sm:px-5">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#2A57B4] text-white"><i class="bx bx-purchase-tag text-lg" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <h2 id="tag-title" class="text-[15px] font-semibold text-slate-900">Tag clearance</h2>
                        <p class="text-xs text-slate-600">Pick a status for the semester. The student is notified.</p>
                    </div>
                </div>
                <form wire:submit.prevent="submitTag" class="space-y-4 p-4 sm:p-5">
                    {{-- Status: three large buttons, each in its own colour once picked --}}
                    <fieldset>
                        <legend class="sr-only">Status</legend>
                        <div class="grid grid-cols-3 gap-2 sm:gap-3">
                            @foreach ($statuses as $status)
                                @php $t = $statusTheme($status); $sel = $tagStatus === $status; @endphp
                                <button type="button" wire:click="$set('tagStatus', '{{ $status }}')" aria-pressed="{{ $sel ? 'true' : 'false' }}"
                                        class="flex min-h-[76px] flex-col items-center justify-center gap-1.5 rounded-xl border-2 bg-white px-2 py-3 text-sm font-semibold shadow-sm transition focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30 sm:min-h-[88px] sm:text-[15px]
                                            {{ $sel ? $t['badge'] . ' border-current shadow-md' : 'border-white text-slate-700 hover:border-slate-200 hover:shadow' }}">
                                    <i class='bx {{ $t['icon'] }} text-2xl {{ $t['iconColor'] }}' aria-hidden="true"></i>
                                    <span class="truncate">{{ $status }}</span>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,14rem)_minmax(0,1fr)_auto] sm:items-end">
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600">Semester</span>
                            <select wire:model="tagSemesterId"
                                    class="h-9 w-full rounded-lg border-slate-300 bg-white py-0 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm [@media(pointer:coarse)]:h-11">
                                <option value="">Select semester…</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}" @disabled(! $semester->isOpen())>{{ \Illuminate\Support\Str::limit($semester->label(), 40) }}{{ $semester->pickerSuffix() }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-slate-600">Remarks <span class="font-normal text-slate-500">(optional)</span></span>
                            <input type="text" wire:model="tagRemarks" placeholder="Add a note about this tag…"
                                   class="h-9 w-full rounded-lg border-slate-300 bg-white px-3 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm [@media(pointer:coarse)]:h-11">
                        </label>
                        <button type="submit" wire:loading.attr="disabled" wire:target="submitTag"
                                class="inline-flex min-h-9 w-full items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-4 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] disabled:opacity-60 sm:w-auto [@media(pointer:coarse)]:min-h-11">
                            <span wire:loading.remove wire:target="submitTag">Submit tag</span>
                            <span wire:loading wire:target="submitTag" class="inline-flex items-center gap-1.5"><i class='bx bx-loader-alt animate-spin' aria-hidden="true"></i> Submitting…</span>
                        </button>
                    </div>
                </form>
            </section>

            {{-- HISTORY, GROUPED BY SEMESTER — CURRENT ALWAYS ON TOP --}}
            <div>
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-[#2A57B4]">Clearance History</p>

                <div class="space-y-3 sm:space-y-4">
                    @forelse($historyGroups as $group)
                        @php $groupTheme = $statusTheme($group['latest_status']); @endphp
                        <div x-data="{ open: false }"
                            class="overflow-hidden rounded-2xl border bg-white {{ $group['is_current'] ? 'border-[#2A57B4]/40 ring-1 ring-[#2A57B4]/10' : $groupTheme['border'] }}">

                            {{-- GROUP HEADER --}}
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 px-4 py-3 sm:px-6 sm:py-4 {{ $group['is_current'] ? 'bg-[#2A57B4]/5' : $groupTheme['bg'] }}">
                                <div class="flex min-w-0 flex-wrap items-center gap-2">
                                    <p class="text-sm font-bold text-slate-800">{{ $group['label'] }}</p>
                                    @if($group['is_current'])
                                        <span class="rounded-full bg-[#2A57B4] px-2 py-0.5 text-[10px] font-bold uppercase text-white">Current</span>
                                    @endif
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide sm:px-3 sm:text-xs {{ $groupTheme['badge'] }}">
                                    <i class='bx {{ $groupTheme['icon'] }} text-sm'></i>
                                    {{ $group['latest_status'] }}
                                </span>
                            </div>

                            {{-- LATEST TAG UPDATE (always visible) --}}
                            @php $latestTheme = $statusTheme($group['latest_entry']['status']); @endphp
                            <div class="border-t border-slate-100 {{ $latestTheme['bg'] }} px-4 py-3 sm:px-6 sm:py-4">
                                {{-- Mobile: "by" line drops below the content. sm+: sits at the right. --}}
                                <div class="flex flex-col gap-1.5 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                                    <div class="flex min-w-0 flex-1 gap-3">
                                        <i class='bx {{ $latestTheme['icon'] }} {{ $latestTheme['iconColor'] }} mt-0.5 shrink-0 text-lg'></i>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $latestTheme['badge'] }}">
                                                    {{ $group['latest_entry']['status'] }}
                                                </span>
                                                <span class="text-xs text-slate-400">{{ $group['latest_entry']['tagged_at'] }}</span>
                                                <span class="text-[10px] font-semibold uppercase tracking-wide text-[#2A57B4]/70">Latest update</span>
                                            </div>
                                            <p class="mt-1 break-words text-sm text-slate-600">{{ $group['latest_entry']['remarks'] ?: 'No remarks.' }}</p>
                                        </div>
                                    </div>
                                    <p class="break-words pl-8 text-xs text-slate-400 sm:shrink-0 sm:pl-0 sm:text-right">by {{ $group['latest_entry']['tagged_by'] }}</p>
                                </div>
                            </div>

                            {{-- SHOW/HIDE TOGGLE FOR OLDER ENTRIES --}}
                            @if($group['older_entries']->isNotEmpty())
                                <button type="button" x-on:click="open = !open"
                                    class="flex w-full items-center justify-center gap-1.5 border-t border-slate-100 px-4 py-3 text-xs font-semibold text-[#2A57B4] transition hover:bg-slate-50 sm:px-6 sm:py-2.5">
                                    <i class='bx' :class="open ? 'bx-chevron-up' : 'bx-chevron-down'"></i>
                                    <span x-text="open ? 'Hide' : 'Show'"></span>
                                    <span>{{ $group['older_entries']->count() }} earlier update{{ $group['older_entries']->count() === 1 ? '' : 's' }}</span>
                                </button>

                                <div x-show="open" x-transition x-cloak class="divide-y divide-slate-100 border-t border-slate-100 bg-slate-50/60">
                                    @foreach($group['older_entries'] as $entry)
                                        @php $entryTheme = $statusTheme($entry['status']); @endphp
                                        <div class="flex flex-col gap-1.5 px-4 py-3 sm:flex-row sm:items-start sm:justify-between sm:gap-4 sm:px-6 {{ $entryTheme['bg'] }}">
                                            <div class="flex min-w-0 flex-1 gap-3">
                                                <i class='bx {{ $entryTheme['icon'] }} {{ $entryTheme['iconColor'] }} mt-0.5 shrink-0 text-base'></i>
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $entryTheme['badge'] }}">
                                                            {{ $entry['status'] }}
                                                        </span>
                                                        <span class="text-xs text-slate-400">{{ $entry['tagged_at'] }}</span>
                                                    </div>
                                                    <p class="mt-1 break-words text-sm text-slate-600">{{ $entry['remarks'] ?: 'No remarks.' }}</p>
                                                </div>
                                            </div>
                                            <p class="break-words pl-7 text-xs text-slate-400 sm:shrink-0 sm:pl-0 sm:text-right">by {{ $entry['tagged_by'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500 sm:px-6 sm:py-10">
                            No clearance history yet for this student.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>