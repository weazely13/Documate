<div class="space-y-6">
    <a href="{{ route($backRoute) }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-[#2A57B4]">
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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 lg:items-start">

        {{-- LEFT: 1/4 — STUDENT INFORMATION (unchanged) --}}
        <div class="space-y-4 lg:col-span-1">

            {{-- IDENTITY CARD --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="bg-[#2A57B4] px-6 py-8 text-center text-white">
                    @if($user->profile_picture)
                        <img src="{{ asset('storage/' . $user->profile_picture) }}" class="mx-auto h-20 w-20 rounded-full object-cover ring-4 ring-white/25">
                    @else
                        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-white/15 text-2xl font-bold ring-4 ring-white/25">
                            {{ $initials }}
                        </div>
                    @endif
                    <h1 class="mt-4 truncate text-lg font-bold leading-tight" title="{{ $fullName }}">{{ $fullName }}</h1>
                    <p class="mt-0.5 truncate text-xs text-white/80">{{ $user->student_number ?: 'No student number' }}</p>
                    <p class="truncate text-xs text-white/70">{{ $user->role->role_name ?? 'Student' }}</p>

                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <span class="max-w-full truncate rounded-full bg-white/15 px-3 py-1 text-[10px] font-semibold uppercase tracking-wide" title="{{ $user->organization?->name ?: 'Unassigned' }}">
                            {{ $user->organization?->name ?: 'Unassigned' }}
                        </span>
                        <span class="rounded-full px-3 py-1 text-[10px] font-semibold uppercase tracking-wide {{ ($user->account_status ?? '') === 'active' ? 'bg-emerald-400/20 text-emerald-50' : 'bg-rose-400/20 text-rose-50' }}">
                            {{ ucfirst(str_replace('_', ' ', $user->account_status ?? 'inactive')) }}
                        </span>
                    </div>
                </div>

                <dl class="space-y-3 px-6 py-5 text-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-[#2A57B4]">Personal Information</p>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Sex</dt><dd class="font-medium text-slate-800">{{ $user->sex ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Birth Date</dt><dd class="font-medium text-slate-800">{{ $user->date_of_birth ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="shrink-0 text-slate-500">Email</dt><dd class="truncate font-medium text-slate-800" title="{{ $user->email ?: '-' }}">{{ $user->email ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Contact No.</dt><dd class="font-medium text-slate-800">{{ $user->contact_number ?: '-' }}</dd></div>
                </dl>
            </div>

            {{-- ACADEMIC INFO CARD --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="mb-4 text-[10px] font-bold uppercase tracking-wider text-[#2A57B4]">Academic Information</p>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="shrink-0 text-slate-500">College</dt><dd class="truncate text-right font-medium text-slate-800" title="{{ $user->college ?: '-' }}">{{ $user->college ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="shrink-0 text-slate-500">Program</dt><dd class="truncate text-right font-medium text-slate-800" title="{{ $programLabel }}">{{ $programLabel }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="shrink-0 text-slate-500">Year &amp; Section</dt><dd class="truncate text-right font-medium text-slate-800" title="{{ $yearLevelLabel }}{{ $user->section ? ' - ' . $user->section : '' }}">{{ $yearLevelLabel }}{{ $user->section ? ' - ' . $user->section : '' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Academic Status</dt><dd class="font-medium text-slate-800">{{ $user->academic_status ?: '-' }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- RIGHT: 3/4 — TAGGING + HISTORY --}}
        <div class="space-y-6 lg:col-span-3">

            {{-- TAG FORM --}}
            <div class="rounded-2xl border border-[#2A57B4]/20 bg-[#2A57B4]/[0.03] p-6">
                <p class="mb-4 text-xs font-bold uppercase tracking-wider text-[#2A57B4]">Tag Clearance</p>

                <form wire:submit.prevent="submitTag" class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Semester</label>
                        <select wire:model="tagSemesterId"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-semibold outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
                            <option value="">Select semester...</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}">{{ \Illuminate\Support\Str::limit($semester->label(), 40) }}{{ $semester->is_current ? ' (Current)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">Status</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($statuses as $status)
                                @php
                                    $btnTheme = $statusTheme($status);
                                    $isSelected = $tagStatus === $status;
                                @endphp
                                <button type="button" wire:click="$set('tagStatus', '{{ $status }}')"
                                    class="flex flex-col items-center gap-1.5 rounded-xl border-2 px-3 py-3 text-xs font-bold uppercase tracking-wide transition
                                        {{ $isSelected ? $btnTheme['badge'] . ' border-current shadow-sm' : 'border-slate-200 text-slate-400 hover:border-slate-300 hover:bg-slate-50' }}">
                                    <i class='bx {{ $btnTheme['icon'] }} text-xl {{ $isSelected ? $btnTheme['iconColor'] : 'text-slate-300' }}'></i>
                                    <span class="w-full truncate text-center">{{ $status }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-600">
                            Remarks <span class="font-normal normal-case text-slate-400">(optional)</span>
                        </label>
                        <textarea wire:model="tagRemarks" rows="2" placeholder="Add a note about this tag..."
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10"></textarea>
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="submitTag"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white shadow-sm shadow-[#2A57B4]/30 transition hover:bg-[#214795] disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                        <span wire:loading.remove wire:target="submitTag">Submit Tag</span>
                        <span wire:loading wire:target="submitTag" class="flex items-center gap-2">
                            <i class='bx bx-loader-alt animate-spin'></i> Submitting...
                        </span>
                    </button>
                </form>
            </div>

            {{-- HISTORY, GROUPED BY SEMESTER — CURRENT ALWAYS ON TOP (only this block's styling changed) --}}
            <div>
                <p class="mb-3 text-xs font-bold uppercase tracking-wider text-[#2A57B4]">Clearance History</p>

                <div class="space-y-4">
                    @forelse($historyGroups as $group)
                        @php $groupTheme = $statusTheme($group['latest_status']); @endphp
                        <div x-data="{ open: false }"
                            class="overflow-hidden rounded-2xl border bg-white {{ $group['is_current'] ? 'border-[#2A57B4]/40 ring-1 ring-[#2A57B4]/10' : $groupTheme['border'] }}">

                            {{-- GROUP HEADER --}}
                            <div class="flex items-center justify-between gap-3 px-6 py-4 {{ $group['is_current'] ? 'bg-[#2A57B4]/5' : $groupTheme['bg'] }}">
                                <div class="flex min-w-0 items-center gap-2">
                                    <p class="truncate text-sm font-bold text-slate-800" title="{{ $group['label'] }}">{{ $group['label'] }}</p>
                                    @if($group['is_current'])
                                        <span class="shrink-0 rounded-full bg-[#2A57B4] px-2 py-0.5 text-[10px] font-bold uppercase text-white">Current</span>
                                    @endif
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $groupTheme['badge'] }}">
                                    <i class='bx {{ $groupTheme['icon'] }} text-sm'></i>
                                    {{ $group['latest_status'] }}
                                </span>
                            </div>

                            {{-- LATEST TAG UPDATE (always visible) --}}
                            @php $latestTheme = $statusTheme($group['latest_entry']['status']); @endphp
                            <div class="border-t border-slate-100 {{ $latestTheme['bg'] }} px-6 py-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex min-w-0 flex-1 gap-3">
                                        <i class='bx {{ $latestTheme['icon'] }} {{ $latestTheme['iconColor'] }} mt-0.5 shrink-0 text-lg'></i>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $latestTheme['badge'] }}">
                                                    {{ $group['latest_entry']['status'] }}
                                                </span>
                                                <span class="text-xs text-slate-400">{{ $group['latest_entry']['tagged_at'] }}</span>
                                                <span class="text-[10px] font-semibold uppercase tracking-wide text-[#2A57B4]/70">Latest update</span>
                                            </div>
                                            <p class="mt-1 line-clamp-2 text-sm text-slate-600" title="{{ $group['latest_entry']['remarks'] ?: 'No remarks.' }}">{{ $group['latest_entry']['remarks'] ?: 'No remarks.' }}</p>
                                        </div>
                                    </div>
                                    <p class="max-w-[10rem] shrink-0 truncate text-right text-xs text-slate-400" title="{{ $group['latest_entry']['tagged_by'] }}">by {{ $group['latest_entry']['tagged_by'] }}</p>
                                </div>
                            </div>

                            {{-- SHOW/HIDE TOGGLE FOR OLDER ENTRIES --}}
                            @if($group['older_entries']->isNotEmpty())
                                <button type="button" x-on:click="open = !open"
                                    class="flex w-full items-center justify-center gap-1.5 border-t border-slate-100 px-6 py-2.5 text-xs font-semibold text-[#2A57B4] transition hover:bg-slate-50">
                                    <i class='bx' :class="open ? 'bx-chevron-up' : 'bx-chevron-down'"></i>
                                    <span x-text="open ? 'Hide' : 'Show'"></span>
                                    <span>{{ $group['older_entries']->count() }} earlier update{{ $group['older_entries']->count() === 1 ? '' : 's' }}</span>
                                </button>

                                <div x-show="open" x-transition x-cloak class="divide-y divide-slate-100 border-t border-slate-100 bg-slate-50/60">
                                    @foreach($group['older_entries'] as $entry)
                                        @php $entryTheme = $statusTheme($entry['status']); @endphp
                                        <div class="flex items-start justify-between gap-4 px-6 py-3 {{ $entryTheme['bg'] }}">
                                            <div class="flex min-w-0 flex-1 gap-3">
                                                <i class='bx {{ $entryTheme['icon'] }} {{ $entryTheme['iconColor'] }} mt-0.5 shrink-0 text-base'></i>
                                                <div class="min-w-0">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $entryTheme['badge'] }}">
                                                            {{ $entry['status'] }}
                                                        </span>
                                                        <span class="text-xs text-slate-400">{{ $entry['tagged_at'] }}</span>
                                                    </div>
                                                    <p class="mt-1 line-clamp-2 text-sm text-slate-600" title="{{ $entry['remarks'] ?: 'No remarks.' }}">{{ $entry['remarks'] ?: 'No remarks.' }}</p>
                                                </div>
                                            </div>
                                            <p class="max-w-[10rem] shrink-0 truncate text-right text-xs text-slate-400" title="{{ $entry['tagged_by'] }}">by {{ $entry['tagged_by'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-sm text-slate-500">
                            No clearance history yet for this student.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>