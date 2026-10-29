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
    <x-admin.page-header
        title="Clearance Profile"
        description="Review this student's clearance history and tag their status per semester."
        :crumbs="['Admin' => $homeUrl, 'Clearance Monitoring' => route($backRoute), $fullName => null]">
        <x-slot:actions>
            <a href="{{ route($backRoute) }}" wire:navigate
            class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 sm:w-auto sm:py-2">
                <i class='bx bx-arrow-back'></i> Back to list
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 lg:items-start">

        {{-- LEFT: student information --}}
        <div class="space-y-6 lg:col-span-1">

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center gap-4 bg-gradient-to-br from-[#2A57B4] to-[#1d3f85] px-5 py-5 text-white lg:flex-col lg:gap-0 lg:px-6 lg:py-8 lg:text-center">
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

            <x-admin.section title="Tag clearance" description="Applies to the selected semester.">
                <form wire:submit.prevent="submitTag" class="space-y-3">

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Semester</label>
                            <select wire:model="tagSemesterId"
                                    class="w-full rounded-lg border-gray-300 py-2 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-1.5 sm:text-sm">
                                <option value="">Select semester…</option>
                                @foreach ($semesters as $semester)
                                    <option value="{{ $semester->id }}" @disabled(! $semester->hasStarted())>{{ \Illuminate\Support\Str::limit($semester->label(), 40) }}{{ $semester->pickerSuffix() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-600">Status</label>
                            <div class="grid grid-cols-3 gap-1.5">
                                @foreach ($statuses as $status)
                                    @php $t = $statusTheme($status); $sel = $tagStatus === $status; @endphp
                                    <button type="button" wire:click="$set('tagStatus', '{{ $status }}')" aria-pressed="{{ $sel ? 'true' : 'false' }}"
                                            class="flex items-center justify-center gap-1 rounded-lg border px-2 py-2 text-xs font-medium transition sm:py-1.5
                                                {{ $sel ? $t['badge'] . ' border-current' : 'border-gray-200 text-gray-500 hover:border-gray-300 hover:bg-gray-50' }}">
                                        <i class='bx {{ $t['icon'] }} text-sm {{ $sel ? $t['iconColor'] : 'text-gray-300' }}'></i>
                                        <span class="truncate">{{ $status }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                        <div class="min-w-0 flex-1">
                            <label class="mb-1 block text-xs font-medium text-gray-600">Remarks <span class="font-normal text-gray-400">(optional)</span></label>
                            <input type="text" wire:model="tagRemarks" placeholder="Add a note about this tag…"
                                class="w-full rounded-lg border-gray-300 py-2 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:py-1.5 sm:text-sm">
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="submitTag"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto sm:py-1.5">
                            <span wire:loading.remove wire:target="submitTag">Submit tag</span>
                            <span wire:loading wire:target="submitTag" class="flex items-center gap-2"><i class='bx bx-loader-alt animate-spin'></i> Submitting…</span>
                        </button>
                    </div>
                </form>
            </x-admin.section>

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