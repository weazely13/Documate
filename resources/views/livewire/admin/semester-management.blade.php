@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $fieldClass = 'rounded-lg border-gray-300 py-2.5 text-base shadow-sm sm:py-2 sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';
@endphp

<div>
    <x-admin.page-header
        title="Semesters & School Years"
        description="Create semesters, set which one is current, or remove ones you no longer need."
        :crumbs="['Admin' => $homeUrl, 'Semesters & School Years' => null]" />

    @include('livewire.admin._admin-nav')

    <div class="space-y-6">

        {{-- Create --}}
        <x-admin.section title="Create a new semester" description="It's added as inactive. Set it as current when you're ready to start tagging against it.">
            <form wire:submit.prevent="createSemester" class="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">School year</label>
                    <input type="text" wire:model="school_year" placeholder="2025-2026" inputmode="numeric" class="w-full {{ $fieldClass }}">
                    @error('school_year') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">Semester</label>
                    <select wire:model="semester_label" class="w-full {{ $fieldClass }}">
                        <option value="">Select…</option>
                        <option value="First">First</option>
                        <option value="Second">Second</option>
                        <option value="Summer">Summer</option>
                    </select>
                    @error('semester_label') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="createSemester"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-[#2A57B4] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 sm:w-auto sm:py-2">
                    <i class='bx bx-plus text-base'></i> Create semester
                </button>
            </form>
        </x-admin.section>

        {{-- List --}}
        <x-admin.section title="All semesters" :description="$semesters->count() . ' ' . \Illuminate\Support\Str::plural('semester', $semesters->count())" :padded="false">
            <div class="divide-y divide-gray-100">
                @forelse ($semesters as $semester)
                    <div wire:key="semester-{{ $semester->id }}"
                         class="flex flex-col gap-3 px-4 py-4 transition-colors hover:bg-gray-50/70 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $semester->is_current ? 'bg-[#2A57B4] text-white' : 'bg-gray-100 text-gray-400' }}">
                                <i class='bx bx-calendar text-lg'></i>
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900">{{ $semester->label() }}</p>
                                @if ($semester->is_current)
                                    <span class="mt-0.5 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-emerald-200">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Current
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Inactive</span>
                                @endif
                            </div>
                        </div>

                        <div class="flex gap-2 sm:shrink-0">
                            @unless ($semester->is_current)
                                <button type="button" wire:click="setCurrent({{ $semester->id }})"
                                        wire:confirm="Set {{ $semester->label() }} as the current semester? This closes out the current semester: any student who was never tagged will automatically be marked Uncleared for it."
                                        class="flex-1 rounded-lg border border-[#2A57B4]/30 bg-[#2A57B4]/5 px-3 py-2.5 text-xs font-semibold text-[#2A57B4] transition hover:bg-[#2A57B4]/10 sm:flex-none sm:py-1.5">
                                    Set as current
                                </button>
                            @endunless
                            <button type="button" wire:click="confirmDelete({{ $semester->id }})"
                                    class="flex-1 rounded-lg border border-red-200 px-3 py-2.5 text-xs font-semibold text-red-600 transition hover:bg-red-50 sm:flex-none sm:py-1.5">
                                Delete
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-12 text-center text-sm text-gray-400">No semesters created yet.</p>
                @endforelse
            </div>
        </x-admin.section>
    </div>

    {{-- Delete confirmation --}}
    @if ($pendingDeleteId)
        <div class="fixed inset-0 z-[10000] flex items-end justify-center bg-black/60 sm:items-center sm:px-4"
             wire:click.self="cancelDelete" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-t-2xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom,0px))] shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-50 text-2xl text-red-500">!</div>
                    <div class="min-w-0">
                        <h3 class="text-xl font-semibold text-gray-900">Delete this semester?</h3>

                        @if ($pendingDeleteClearanceCount > 0 || $pendingDeleteSettingCount > 0)
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                This semester is linked to:
                                <ul class="mt-1 list-inside list-disc">
                                    @if ($pendingDeleteClearanceCount > 0)
                                        <li>{{ $pendingDeleteClearanceCount }} clearance status record(s)</li>
                                    @endif
                                    @if ($pendingDeleteSettingCount > 0)
                                        <li>{{ $pendingDeleteSettingCount }} verification period(s)</li>
                                    @endif
                                </ul>
                                <p class="mt-2">Those records will <strong>not</strong> be deleted, but they'll lose their link to this semester.</p>
                            </div>
                        @else
                            <p class="mt-2 text-sm leading-6 text-gray-500">No records are linked to this semester yet.</p>
                        @endif

                        <label class="mt-4 flex items-start gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model.live="confirmDeleteChecked" class="mt-0.5 rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                            I understand and want to delete this semester.
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end sm:gap-3">
                    <button type="button" wire:click="cancelDelete"
                            class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 sm:py-2">Cancel</button>
                    <button type="button" wire:click="deleteSemester" @disabled(! $confirmDeleteChecked)
                            class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-40 sm:py-2">
                        Delete semester
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>