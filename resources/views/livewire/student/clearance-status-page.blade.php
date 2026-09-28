<div class="space-y-6" wire:poll.5s>
    @php
        $statusTheme = fn (string $status) => match ($status) {
            'Cleared' => [
                'badge' => 'bg-emerald-500/10 text-emerald-700 border-emerald-200/80',
                'border' => 'border-emerald-200/60',
                'bg' => 'bg-emerald-50/40',
                'icon' => 'bx-check-shield',
                'iconWrap' => 'bg-emerald-500 text-white shadow-emerald-500/25',
                'word' => 'text-emerald-600',
                'accent' => 'bg-emerald-500'
            ],
            'Pending' => [
                'badge' => 'bg-amber-500/10 text-amber-700 border-amber-200/80',
                'border' => 'border-amber-200/60',
                'bg' => 'bg-amber-50/40',
                'icon' => 'bx-time-five',
                'iconWrap' => 'bg-amber-500 text-white shadow-amber-500/25',
                'word' => 'text-amber-600',
                'accent' => 'bg-amber-500'
            ],
            'Uncleared' => [
                'badge' => 'bg-rose-500/10 text-rose-700 border-rose-200/80',
                'border' => 'border-rose-200/60',
                'bg' => 'bg-rose-50/40',
                'icon' => 'bx-x-circle',
                'iconWrap' => 'bg-rose-500 text-white shadow-rose-500/25',
                'word' => 'text-rose-600',
                'accent' => 'bg-rose-500'
            ],
            default => [
                'badge' => 'bg-slate-500/10 text-slate-700 border-slate-200',
                'border' => 'border-slate-200',
                'bg' => 'bg-slate-50/40',
                'icon' => 'bx-help-circle',
                'iconWrap' => 'bg-slate-500 text-white shadow-slate-500/25',
                'word' => 'text-slate-600',
                'accent' => 'bg-slate-400'
            ],
        };
        $currentTheme = $statusTheme($currentStatus['status']);
    @endphp

    {{-- CURRENT STATUS HERO CARD (Solid line removed) --}}
    <section class="relative mx-auto w-full max-w-2xl overflow-hidden rounded-2xl border {{ $currentTheme['border'] }} {{ $currentTheme['bg'] }} p-6 shadow-md backdrop-blur-xs transition-all hover:scale-[1.01] sm:p-8">
        <div class="relative z-10 flex h-full flex-col justify-between space-y-6">
            {{-- Card Top Header: Academic Icon + Clearance Badge --}}
            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl shadow-md {{ $currentTheme['iconWrap'] }}">
                        <i class='bx {{ $currentTheme['icon'] }} text-2xl'></i>
                    </span>
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400">Official Record</span>
                        <p class="text-xs font-bold text-slate-700 sm:text-sm">{{ $currentStatus['period_label'] }}</p>
                    </div>
                </div>

                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold uppercase tracking-wider shadow-2xs {{ $currentTheme['badge'] }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $currentTheme['accent'] }} animate-pulse"></span>
                    Current Clearance
                </span>
            </div>

            {{-- Card Center: Prominent Status Outcome --}}
            <div>
                <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Clearance Status</span>
                <p class="text-3xl font-black tracking-tight sm:text-4xl {{ $currentTheme['word'] }}">
                    {{ $currentStatus['status_word'] }}
                </p>
            </div>

            {{-- Card Footer: Embossed Credit Card Style Field Grid --}}
            <div class="grid grid-cols-1 gap-3 border-t border-slate-200/60 pt-4 text-xs sm:grid-cols-3 sm:gap-4">
                <div>
                    <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Student Holder</span>
                    <p class="mt-0.5 font-bold tracking-tight text-slate-900">{{ $fullName ?: ($user->first_name ?? 'Student') }}</p>
                    <p class="text-[10px] font-medium text-slate-500">{{ $user->student_number ?: 'No ID' }} &bull; {{ $formattedYearLevel }}</p>
                </div>
                <div>
                    <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Tagged By</span>
                    <p class="mt-0.5 font-semibold text-slate-800 truncate">{{ $currentStatus['tagged_by'] ?: '-' }}</p>
                </div>
                <div class="sm:text-right">
                    <span class="block text-[9px] font-extrabold uppercase tracking-wider text-slate-400">Remarks</span>
                    <p class="mt-0.5 font-medium text-slate-700 truncate" title="{{ $currentStatus['remarks'] }}">{{ $currentStatus['remarks'] ?: '-' }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- CLEARANCE RECORDS SECTION --}}
    <div class="space-y-4 pt-2">
        {{-- Section Header & Filter Controls --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-black tracking-tight text-slate-900 sm:text-xl">Status by Semester</h2>
                <p class="text-xs font-medium text-slate-500">Live clearance status records.</p>
            </div>

            {{-- Discrete Top-Right Filter Toolbar --}}
            <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                <div class="relative">
                    <select wire:model.live="semesterFilter"
                            class="appearance-none rounded-lg border border-slate-200/80 bg-slate-50/80 py-1.5 pl-3 pr-7 text-xs font-semibold text-slate-600 transition hover:bg-white focus:border-[#2A57B4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/10">
                        <option value="">All Semesters</option>
                        @foreach($semesterOptions as $sem)
                            <option value="{{ $sem }}">{{ $sem }}</option>
                        @endforeach
                    </select>
                    <i class='bx bx-chevron-down pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-sm text-slate-400'></i>
                </div>

                <div class="relative">
                    <select wire:model.live="historyStatusFilter"
                            class="appearance-none rounded-lg border border-slate-200/80 bg-slate-50/80 py-1.5 pl-3 pr-7 text-xs font-semibold text-slate-600 transition hover:bg-white focus:border-[#2A57B4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/10">
                        <option value="">All Statuses</option>
                        @foreach($statusOptions as $statusOption)
                            <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                        @endforeach
                    </select>
                    <i class='bx bx-chevron-down pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-sm text-slate-400'></i>
                </div>

                <div class="relative">
                    <select wire:model.live="sortBy"
                            class="appearance-none rounded-lg border border-slate-200/80 bg-slate-50/80 py-1.5 pl-3 pr-7 text-xs font-semibold text-slate-600 transition hover:bg-white focus:border-[#2A57B4] focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#2A57B4]/10">
                        <option value="latest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                    <i class='bx bx-chevron-down pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-sm text-slate-400'></i>
                </div>
            </div>
        </div>

        {{-- GRID OF CREDIT CARD STYLE SEMESTER CARDS (Solid line removed) --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @forelse($history as $entry)
                @php
                    $entryTheme = $statusTheme($entry['status']);
                    $entryIsCurrent = $entry['is_current'] ?? false;
                @endphp
                <div class="group relative overflow-hidden rounded-2xl border {{ $entryTheme['border'] }} {{ $entryTheme['bg'] }} p-5 shadow-2xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md">
                    <div class="relative z-10 flex h-full flex-col justify-between space-y-4">
                        {{-- Top Bar: School/Clearance Icon + Term & Badge --}}
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg transition-transform group-hover:scale-105 {{ $entryTheme['iconWrap'] }}">
                                    <i class='bx {{ $entryTheme['icon'] }} text-lg'></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-extrabold text-slate-900">{{ $entry['period_label'] }}</p>
                                    @if($entryIsCurrent)
                                        <span class="inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-[#2A57B4]">
                                            <span class="h-1 w-1 rounded-full bg-[#2A57B4] animate-pulse"></span>
                                            Current Term
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider shadow-2xs {{ $entryTheme['badge'] }}">
                                {{ $entry['status'] }}
                            </span>
                        </div>

                        {{-- Card Body: Clearance State Word --}}
                        <div>
                            <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Clearance Outcome</span>
                            <p class="text-2xl font-black tracking-tight {{ $entryTheme['word'] }}">
                                {{ $entry['status'] }}
                            </p>
                        </div>

                        {{-- Card Bottom: Credit-Card Style Multi-Column Footer --}}
                        <div class="grid grid-cols-3 gap-2 border-t border-slate-200/50 pt-3 text-xs">
                            <div class="min-w-0">
                                <span class="block text-[8px] font-extrabold uppercase tracking-wider text-slate-400">Tagged By</span>
                                <p class="mt-0.5 truncate font-semibold text-slate-800">{{ $entry['tagged_by'] ?: '-' }}</p>
                            </div>
                            <div class="min-w-0">
                                <span class="block text-[8px] font-extrabold uppercase tracking-wider text-slate-400">Remarks</span>
                                <p class="mt-0.5 truncate font-medium text-slate-600" title="{{ $entry['remarks'] }}">{{ $entry['remarks'] ?: 'No remarks.' }}</p>
                            </div>
                            <div class="min-w-0 text-right">
                                <span class="block text-[8px] font-extrabold uppercase tracking-wider text-slate-400">Last Updated</span>
                                <p class="mt-0.5 font-medium text-slate-500">{{ $entry['last_updated'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-300 bg-white py-12 px-4 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                        <i class='bx bx-book-bookmark text-2xl'></i>
                    </div>
                    <p class="mt-3 text-sm font-bold text-slate-700">No clearance records found</p>
                    <p class="mt-1 text-xs text-slate-400">Try adjusting your filters or search options.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>