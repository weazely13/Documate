<div class="space-y-6">
    @include('livewire.admin._admin-nav')

    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Semesters &amp; School Years</h1>
        <p class="mt-1 text-sm text-slate-500">Create semesters, set which one is current, or remove ones you no longer need.</p>
    </div>

    {{-- CREATE FORM --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-center gap-2">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#2A57B4]/10 text-[#2A57B4]">
                <i class='bx bx-plus text-lg'></i>
            </span>
            <div>
                <p class="text-sm font-bold text-slate-800">Create a new semester</p>
                <p class="text-xs text-slate-400">It's added as inactive — set it as current when you're ready to start tagging against it.</p>
            </div>
        </div>

        <form wire:submit.prevent="createSemester" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">School Year</label>
                <input type="text" wire:model="school_year" placeholder="2025-2026"
                    class="w-44 rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
                @error('school_year') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400">Semester</label>
                <select wire:model="semester_label"
                    class="w-44 rounded-xl border border-slate-300 px-3 py-2.5 text-sm font-medium outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
                    <option value="">Select...</option>
                    <option value="First">First</option>
                    <option value="Second">Second</option>
                    <option value="Summer">Summer</option>
                </select>
                @error('semester_label') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>

            <button type="submit"
                class="inline-flex items-center gap-2 rounded-xl bg-[#2A57B4] px-5 py-2.5 text-sm font-semibold text-white shadow-sm shadow-[#2A57B4]/30 transition hover:bg-[#214795]">
                <i class='bx bx-plus text-base'></i> Create Semester
            </button>
        </form>
    </div>

    {{-- LIST --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-slate-100 px-6 py-4">
            <p class="text-sm font-bold text-slate-800">All semesters</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($semesters as $semester)
                <div class="flex flex-wrap items-center justify-between gap-3 px-6 py-4 transition hover:bg-slate-50/70">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl {{ $semester->is_current ? 'bg-[#2A57B4] text-white' : 'bg-slate-100 text-slate-400' }}">
                            <i class='bx bx-calendar text-lg'></i>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $semester->label() }}</p>
                            @if($semester->is_current)
                                <span class="mt-0.5 inline-block rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Current</span>
                            @else
                                <span class="mt-0.5 inline-block text-[10px] font-medium uppercase tracking-wide text-slate-400">Inactive</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        @unless($semester->is_current)
                            <button
                                wire:click="setCurrent({{ $semester->id }})"
                                onclick="return confirm('Set {{ $semester->label() }} as the current semester?\n\nThis will close out the current semester: any student who was never tagged will automatically be marked Uncleared for it.')"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-[#2A57B4] hover:bg-[#2A57B4]/10">
                                <i class='bx bx-check-circle text-sm'></i> Set as Current
                            </button>
                        @endunless

                        <button wire:click="confirmDelete({{ $semester->id }})"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                            <i class='bx bx-trash text-sm'></i> Delete
                        </button>
                    </div>
                </div>
            @empty
                <div class="px-6 py-10 text-center text-sm text-slate-400">No semesters created yet.</div>
            @endforelse
        </div>
    </div>

    {{-- DELETE CONFIRMATION --}}
    @if($pendingDeleteId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" wire:click.self="cancelDelete">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-3 flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                        <i class='bx bx-error text-lg'></i>
                    </span>
                    <h4 class="text-sm font-bold text-slate-800">Delete this semester?</h4>
                </div>

                @if($pendingDeleteClearanceCount > 0 || $pendingDeleteSettingCount > 0)
                    <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        This semester is linked to:
                        <ul class="mt-1 list-inside list-disc">
                            @if($pendingDeleteClearanceCount > 0)
                                <li>{{ $pendingDeleteClearanceCount }} clearance status record(s)</li>
                            @endif
                            @if($pendingDeleteSettingCount > 0)
                                <li>{{ $pendingDeleteSettingCount }} verification period(s)</li>
                            @endif
                        </ul>
                        Those records will <strong>not</strong> be deleted, but they'll lose their link to this semester.
                    </div>
                @else
                    <p class="mb-3 text-sm text-slate-500">No records are linked to this semester yet.</p>
                @endif

                <label class="mb-4 flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="confirmDeleteChecked" class="rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]">
                    I understand and want to delete this semester.
                </label>

                <div class="flex justify-end gap-2">
                    <button wire:click="cancelDelete" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button
                        wire:click="deleteSemester"
                        @disabled(!$confirmDeleteChecked)
                        class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-40">
                        Delete Semester
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>