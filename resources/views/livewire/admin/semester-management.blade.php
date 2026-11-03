@php
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $control = 'h-9 w-full rounded-lg border-slate-300 bg-white py-0 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 cq-sm:text-sm [@media(pointer:coarse)]:h-11';
    $label = 'block text-sm font-medium text-slate-700';
    $error = 'mt-1 text-sm text-rose-700';
    $btn = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $btnPrimary = $btn . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $btnSecondary = $btn . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $rowBtn = 'inline-flex min-h-8 items-center rounded-lg px-3 text-xs font-medium transition-colors [@media(pointer:coarse)]:min-h-11 ' . $focus;

    // Where each semester stands against today's date (DESIGN.md pills: tint + deep text + 200 ring).
    $phaseChip = [
        'Open' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'Upcoming' => 'bg-sky-50 text-[#1E4090] ring-sky-200',
        'Ended' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'Dates needed' => 'bg-amber-50 text-amber-800 ring-amber-200',
    ];
    $undated = $semesters->reject(fn ($s) => $s->isDated());
@endphp

{{-- Semesters & school years, inside the Users and accounts manager (same shell, same card and table style). --}}
<x-admin.users-manager active="semesters">
<div class="space-y-5">

    @if ($undated->isNotEmpty())
        <p class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900" role="status">
            <i class='bx bx-calendar-exclamation mt-0.5 text-base' aria-hidden="true"></i>
            <span><b class="font-semibold">{{ $undated->count() }} {{ \Illuminate\Support\Str::plural('semester', $undated->count()) }} still need dates:</b> {{ $undated->map->label()->implode(', ') }}. Until they have dates they won't switch on by themselves. Use <b class="font-semibold">Add dates</b> on each one.</span>
        </p>
    @endif

    {{-- List --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3">
            <p class="text-sm text-slate-500">
                <span class="tabular-nums">{{ $semesters->count() }} {{ \Illuminate\Support\Str::plural('semester', $semesters->count()) }}</span>
                @if ($today)<span class="hidden cq-sm:inline"> · today falls in <span class="font-medium text-slate-700">{{ $today->label() }}</span></span>@endif
            </p>
            <button type="button" wire:click="openCreate" class="{{ $btnPrimary }} !px-3.5">
                <i class='bx bx-plus text-base' aria-hidden="true"></i> Create a semester
            </button>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($semesters as $semester)
                <li wire:key="semester-{{ $semester->id }}">
                    <div class="flex flex-col gap-2 px-4 py-2.5 transition-colors hover:bg-slate-50 cq-sm:flex-row cq-sm:items-center cq-sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $semester->is_current ? 'bg-[#2A57B4] text-white' : 'bg-[#f0f4fb] text-[#1E4090]' }}" aria-hidden="true">
                                <i class='bx bx-calendar text-base'></i>
                            </span>
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-1.5 text-sm">
                                    <span class="truncate font-medium text-slate-900">{{ $semester->label() }}</span>
                                    @if ($semester->is_current)
                                        <span class="inline-flex items-center rounded-full bg-[#e2e9f7] px-2 py-0.5 text-[11px] font-medium text-[#1E4090] ring-1 ring-sky-200">Current</span>
                                    @endif
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 {{ $phaseChip[$semester->phase()] }}">{{ $semester->phase() }}</span>
                                    @if ($semester->finalized_at)
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 ring-1 ring-slate-200" title="Students never tagged were marked Uncleared on {{ $semester->finalized_at->format('M j, Y') }}">Closed out</span>
                                    @endif
                                </p>
                                <p class="text-xs tabular-nums text-slate-500">{{ $semester->dateLabel() ?? 'No dates yet' }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1 cq-sm:shrink-0 cq-sm:justify-end">
                            <button type="button" wire:click="editDates({{ $semester->id }})" class="{{ $rowBtn }} border border-slate-200 bg-white text-slate-700 hover:bg-slate-50">
                                {{ $semester->isDated() ? 'Edit dates' : 'Add dates' }}
                            </button>
                            @unless ($semester->is_current)
                                <button type="button" wire:click="setCurrent({{ $semester->id }})"
                                        wire:confirm="Set {{ $semester->label() }} as the current semester by hand? Normally semesters switch by their dates; use this only to correct it. This closes out the current semester: any student who was never tagged will automatically be marked Uncleared for it."
                                        class="{{ $rowBtn }} text-[#2A57B4] hover:bg-[#f0f4fb]">Set as current</button>
                            @endunless
                            <button type="button" wire:click="confirmDelete({{ $semester->id }})" class="{{ $rowBtn }} text-rose-700 hover:bg-rose-50">Delete</button>
                        </div>
                    </div>
                    @if ($editingId === $semester->id)
                        <form wire:key="semester-dates-{{ $semester->id }}" wire:submit.prevent="saveDates"
                              class="grid grid-cols-1 gap-3 border-t border-slate-100 bg-[#f0f4fb] px-4 py-3 cq-sm:grid-cols-[1fr_1fr_auto] cq-sm:items-end">
                            <label class="block">
                                <span class="{{ $label }}">Start date</span>
                                <input type="date" wire:model="editStartsOn" class="mt-1 px-3 {{ $control }}">
                                @error('editStartsOn') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </label>
                            <label class="block">
                                <span class="{{ $label }}">End date</span>
                                <input type="date" wire:model="editEndsOn" class="mt-1 px-3 {{ $control }}">
                                @error('editEndsOn') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </label>
                            <div class="flex gap-2">
                                <button type="button" wire:click="cancelEditDates" class="{{ $btnSecondary }} flex-1 cq-sm:flex-none">Cancel</button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="saveDates" class="{{ $btnPrimary }} flex-1 cq-sm:flex-none">Save dates</button>
                            </div>
                        </form>
                    @endif
                </li>
            @empty
                <li class="px-5 py-14 text-center text-sm text-slate-500">No semesters created yet.</li>
            @endforelse
        </ul>
    </div>

    {{-- Create a semester (moved to <body>: the shell is a container, which would trap a fixed overlay) --}}
    @if ($showCreate)
        @teleport('body')
        <div class="fixed inset-0 z-[10000] flex items-end justify-center bg-slate-900/50 sm:items-center sm:px-4"
             wire:click.self="closeCreate" wire:keydown.escape.window="closeCreate" role="dialog" aria-modal="true" aria-labelledby="new-sem-title">
            <form wire:submit.prevent="createSemester"
                  class="w-full max-w-lg rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl"
                  x-data x-init="$nextTick(() => $el.querySelector('input')?.focus())">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div class="min-w-0">
                        <h3 id="new-sem-title" class="text-lg font-semibold tracking-[-0.012em] text-slate-900">Create a semester</h3>
                        <p class="mt-0.5 text-sm text-slate-500">It becomes current on its start date by itself.</p>
                    </div>
                    <button type="button" wire:click="closeCreate" aria-label="Close" class="-mr-1 grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 {{ $focus }}">
                        <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="grid grid-cols-1 gap-3 px-5 py-4 sm:grid-cols-2">
                    <label class="block">
                        <span class="{{ $label }}">School year</span>
                        <input type="text" wire:model="school_year" placeholder="2025-2026" inputmode="numeric" class="mt-1 px-3 {{ $control }}">
                        @error('school_year') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </label>
                    <label class="block">
                        <span class="{{ $label }}">Semester</span>
                        <select wire:model="semester_label" class="mt-1 pl-3 pr-8 {{ $control }}">
                            <option value="">Select…</option>
                            <option value="First">First</option>
                            <option value="Second">Second</option>
                            <option value="Summer">Summer</option>
                        </select>
                        @error('semester_label') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </label>
                    <label class="block">
                        <span class="{{ $label }}">Start date</span>
                        <input type="date" wire:model="starts_on" class="mt-1 px-3 {{ $control }}">
                        @error('starts_on') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </label>
                    <label class="block">
                        <span class="{{ $label }}">End date</span>
                        <input type="date" wire:model="ends_on" class="mt-1 px-3 {{ $control }}">
                        @error('ends_on') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </label>
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-3 sm:flex-row sm:justify-end sm:rounded-b-2xl">
                    <button type="button" wire:click="closeCreate" class="{{ $btnSecondary }}">Cancel</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="createSemester" class="{{ $btnPrimary }}">
                        <i class='bx bx-plus text-base' aria-hidden="true"></i> New semester
                    </button>
                </div>
            </form>
        </div>
        @endteleport
    @endif

    {{-- Delete confirmation (moved to <body>: the shell is a container, which would trap a fixed overlay) --}}
    @if ($pendingDeleteId)
        @teleport('body')
        <div class="fixed inset-0 z-[10000] flex items-end justify-center bg-slate-900/50 sm:items-center sm:px-4"
             wire:click.self="cancelDelete" role="alertdialog" aria-modal="true" aria-labelledby="del-sem-title">
            <div class="w-full max-w-md rounded-t-2xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom,0px))] shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-rose-50 text-rose-600"><i class="bx bx-calendar-x text-xl" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <h3 id="del-sem-title" class="text-lg font-semibold tracking-[-0.012em] text-slate-900">Delete this semester?</h3>
                        @if ($pendingDeleteClearanceCount > 0 || $pendingDeleteSettingCount > 0)
                            <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900">
                                This semester is linked to:
                                <ul class="mt-1 list-inside list-disc">
                                    @if ($pendingDeleteClearanceCount > 0)<li>{{ $pendingDeleteClearanceCount }} clearance status record(s)</li>@endif
                                    @if ($pendingDeleteSettingCount > 0)<li>{{ $pendingDeleteSettingCount }} verification period(s)</li>@endif
                                </ul>
                                <p class="mt-1.5">Those records are <strong>not</strong> deleted, but they lose their link to this semester.</p>
                            </div>
                        @else
                            <p class="mt-1 text-sm text-slate-600">No records are linked to this semester yet.</p>
                        @endif
                        <label class="mt-3 flex items-start gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model.live="confirmDeleteChecked" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                            I understand and want to delete this semester.
                        </label>
                    </div>
                </div>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="cancelDelete" class="{{ $btnSecondary }}">Cancel</button>
                    <button type="button" wire:click="deleteSemester" @disabled(! $confirmDeleteChecked) class="{{ $btn }} bg-rose-600 text-white shadow-sm hover:bg-rose-700">Delete semester</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
</x-admin.users-manager>
