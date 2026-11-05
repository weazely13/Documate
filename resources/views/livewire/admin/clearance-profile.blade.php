@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $initials = strtoupper(substr($user->first_name ?? 'S', 0, 1) . substr($user->last_name ?? 'T', 0, 1));
    $fieldClass = 'rounded-lg border-gray-300 py-2.5 text-base shadow-sm sm:py-2 sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';

    $statusTheme = fn (string $status) => match ($status) {
        'Cleared' => ['badge' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'border' => 'border-emerald-200/70', 'bg' => 'bg-emerald-50/30', 'icon' => 'bx-check-shield', 'iconColor' => 'text-emerald-600'],
        'Pending' => ['badge' => 'bg-amber-50 text-amber-700 ring-amber-200', 'border' => 'border-amber-200/70', 'bg' => 'bg-amber-50/30', 'icon' => 'bx-time-five', 'iconColor' => 'text-amber-600'],
        'Uncleared' => ['badge' => 'bg-rose-50 text-rose-700 ring-rose-200', 'border' => 'border-rose-200/70', 'bg' => 'bg-rose-50/30', 'icon' => 'bx-x-circle', 'iconColor' => 'text-rose-600'],
        default => ['badge' => 'bg-gray-100 text-gray-500 ring-gray-200', 'border' => 'border-gray-200', 'bg' => 'bg-gray-50/30', 'icon' => 'bx-help-circle', 'iconColor' => 'text-gray-400'],
    };
@endphp

<div>
    <h1 class="sr-only">Clearance Profile</h1>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 lg:items-start">

        {{-- LEFT: student information --}}
        <div class="space-y-6 lg:col-span-1">

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="relative flex items-center gap-4 bg-gradient-to-br from-[#2A57B4] to-[#1d3f85] px-5 py-5 max-lg:pr-14 text-white lg:flex-col lg:gap-0 lg:px-6 lg:py-8 lg:text-center">
                    {{-- Back to the list, on the card's corner --}}
                    <a href="{{ route($backRoute) }}" wire:navigate title="Back to list" aria-label="Back to list"
                       class="absolute left-2.5 top-2.5 grid h-9 w-9 place-items-center rounded-full bg-white/15 text-white transition hover:bg-white/25 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 max-lg:left-auto max-lg:right-2.5 max-lg:top-1/2 max-lg:-translate-y-1/2">
                        <i class="bx bx-arrow-back text-lg" aria-hidden="true"></i>
                    </a>
                    @if ($user->profile_picture)
                        <img src="{{ asset('storage/' . $user->profile_picture) }}" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover ring-4 ring-white/25 lg:h-20 lg:w-20">
                    @else
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white/15 text-xl font-bold ring-4 ring-white/25 lg:h-20 lg:w-20 lg:text-2xl">{{ $initials }}</div>
                    @endif
                    <div class="min-w-0 lg:mt-4">
                        <h2 class="truncate text-base font-semibold leading-tight lg:text-lg" title="{{ $fullName }}">{{ $fullName }}</h2>
                        <p class="mt-0.5 truncate text-xs text-white/80">{{ $user->student_number ?: 'No student number' }} · {{ $user->role->role_name ?? 'Student' }}</p>
                        <div class="mt-2.5 flex flex-wrap gap-1.5 lg:justify-center">
                            <span class="max-w-full truncate rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-medium" title="{{ $user->organization?->name ?: 'Unassigned' }}">{{ $user->organization?->name ?: 'Unassigned' }}</span>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-medium {{ ($user->account_status ?? '') === 'active' ? 'bg-emerald-400/20 text-emerald-50' : 'bg-rose-400/20 text-rose-50' }}">
                                {{ ucfirst(str_replace('_', ' ', $user->account_status ?? 'inactive')) }}
                            </span>
                        </div>
                    </div>
                </div>

                <dl class="space-y-3 px-5 py-5 text-sm">
                    <p class="text-xs font-semibold text-[#2A57B4]">Personal information</p>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Sex</dt><dd class="font-medium text-gray-800">{{ $user->sex ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Birth date</dt><dd class="font-medium text-gray-800">{{ $user->date_of_birth ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="shrink-0 text-gray-500">Email</dt><dd class="truncate font-medium text-gray-800" title="{{ $user->email ?: '-' }}">{{ $user->email ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Contact no.</dt><dd class="font-medium text-gray-800">{{ $user->contact_number ?: '-' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <p class="mb-4 text-xs font-semibold text-[#2A57B4]">Academic information</p>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="shrink-0 text-gray-500">College</dt><dd class="truncate text-right font-medium text-gray-800" title="{{ $user->college ?: '-' }}">{{ $user->college ?: '-' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="shrink-0 text-gray-500">Program</dt><dd class="truncate text-right font-medium text-gray-800" title="{{ $programLabel }}">{{ $programLabel }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="shrink-0 text-gray-500">Year &amp; section</dt><dd class="truncate text-right font-medium text-gray-800">{{ $yearLevelLabel }}{{ $user->section ? ' - ' . $user->section : '' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">Academic status</dt><dd class="font-medium text-gray-800">{{ $user->academic_status ?: '-' }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- RIGHT: tagging + history --}}
        <div class="space-y-6 lg:col-span-3">

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
                                    <option value="{{ $semester->id }}" @disabled(! $semester->hasStarted())>{{ \Illuminate\Support\Str::limit($semester->label(), 40) }}{{ $semester->pickerSuffix() }}</option>
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

            {{-- History, grouped by semester (current on top) --}}
            <div>
                <h2 class="mb-3 text-sm font-semibold text-gray-900">Clearance history</h2>

                <div class="space-y-4">
                    @forelse ($historyGroups as $group)
                        @php $gt = $statusTheme($group['latest_status']); $lt = $statusTheme($group['latest_entry']['status']); @endphp
                        <div x-data="{ open: false }" wire:key="history-{{ $loop->index }}"
                             class="overflow-hidden rounded-xl border bg-white shadow-sm {{ $group['is_current'] ? 'border-[#2A57B4]/40 ring-1 ring-[#2A57B4]/10' : $gt['border'] }}">

                            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3.5 sm:px-5 {{ $group['is_current'] ? 'bg-[#2A57B4]/5' : $gt['bg'] }}">
                                <div class="flex min-w-0 items-center gap-2">
                                    <p class="truncate text-sm font-semibold text-gray-900" title="{{ $group['label'] }}">{{ $group['label'] }}</p>
                                    @if ($group['is_current'])
                                        <span class="shrink-0 rounded-full bg-[#2A57B4] px-2 py-0.5 text-[10px] font-medium text-white">Current</span>
                                    @endif
                                </div>
                                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $gt['badge'] }}">
                                    <i class='bx {{ $gt['icon'] }} text-sm'></i>{{ $group['latest_status'] }}
                                </span>
                            </div>

                            <div class="border-t border-gray-100 px-4 py-3.5 sm:px-5 {{ $lt['bg'] }}">
                                <div class="flex gap-3">
                                    <i class='bx {{ $lt['icon'] }} {{ $lt['iconColor'] }} mt-0.5 shrink-0 text-lg'></i>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 {{ $lt['badge'] }}">{{ $group['latest_entry']['status'] }}</span>
                                            <span class="text-xs text-gray-400">{{ $group['latest_entry']['tagged_at'] }}</span>
                                            <span class="text-[11px] font-medium text-[#2A57B4]/70">Latest update</span>
                                        </div>
                                        <p class="mt-1 line-clamp-2 text-sm text-gray-600" title="{{ $group['latest_entry']['remarks'] ?: 'No remarks.' }}">{{ $group['latest_entry']['remarks'] ?: 'No remarks.' }}</p>
                                        <p class="mt-1 truncate text-xs text-gray-400">by {{ $group['latest_entry']['tagged_by'] }}</p>
                                    </div>
                                </div>
                            </div>

                            @if ($group['older_entries']->isNotEmpty())
                                <button type="button" x-on:click="open = !open" :aria-expanded="open"
                                        class="flex w-full items-center justify-center gap-1.5 border-t border-gray-100 px-4 py-3 text-xs font-medium text-[#2A57B4] transition hover:bg-gray-50 sm:py-2.5">
                                    <i class='bx' :class="open ? 'bx-chevron-up' : 'bx-chevron-down'"></i>
                                    <span x-text="open ? 'Hide' : 'Show'"></span>
                                    <span>{{ $group['older_entries']->count() }} earlier {{ \Illuminate\Support\Str::plural('update', $group['older_entries']->count()) }}</span>
                                </button>

                                <div x-show="open" x-transition x-cloak class="divide-y divide-gray-100 border-t border-gray-100 bg-gray-50/60">
                                    @foreach ($group['older_entries'] as $entry)
                                        @php $et = $statusTheme($entry['status']); @endphp
                                        <div class="flex gap-3 px-4 py-3 sm:px-5 {{ $et['bg'] }}">
                                            <i class='bx {{ $et['icon'] }} {{ $et['iconColor'] }} mt-0.5 shrink-0 text-base'></i>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 {{ $et['badge'] }}">{{ $entry['status'] }}</span>
                                                    <span class="text-xs text-gray-400">{{ $entry['tagged_at'] }}</span>
                                                </div>
                                                <p class="mt-1 line-clamp-2 text-sm text-gray-600" title="{{ $entry['remarks'] ?: 'No remarks.' }}">{{ $entry['remarks'] ?: 'No remarks.' }}</p>
                                                <p class="mt-1 truncate text-xs text-gray-400">by {{ $entry['tagged_by'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-400">No clearance history yet for this student.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>