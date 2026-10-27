@php
    $input = 'rounded-lg border-gray-300 text-base shadow-sm cq-sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';
    $formInput = 'mt-1 w-full ' . $input;
    $label = 'block text-xs font-medium text-gray-600';
    $card = 'rounded-2xl border border-gray-200/70 bg-white shadow-sm';
    $thead = 'bg-gray-50 text-xs font-medium text-gray-500';
    $primaryBtn = 'rounded-lg bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60';
    $secondaryBtn = 'rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50';
    $error = 'mt-1 text-xs text-red-600';

    // Toolbar: a count on the left, the section's main action on the right.
    $toolbar = 'flex items-center justify-between gap-3';
    $addBtn = 'inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700';

    // Filters: search, then every dropdown on a single row that scrolls sideways when narrow.
    $filterBar = $card . ' flex flex-col gap-2 p-2.5 cq-md:flex-row cq-md:items-center';
    $filterSearch = 'w-full rounded-full border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-base focus:border-[#2A57B4] focus:ring-[#2A57B4] cq-sm:text-sm';
    $filterRow = 'flex min-w-0 items-center gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden';
    $pill = 'shrink-0 rounded-full border-gray-200 bg-gray-50 py-1.5 pl-3 pr-8 text-xs font-medium text-gray-700 focus:border-[#2A57B4] focus:ring-[#2A57B4]';
    $clearBtn = 'shrink-0 rounded-full px-2.5 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-800';

    // Tables show once the section is wide enough; below that each row becomes a stacked card.
    $wideTable = 'hidden overflow-x-auto cq-md:block';
    $narrowList = 'divide-y divide-gray-100 cq-md:hidden';
    $rowBtn = 'rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200';
    $rowBtnDanger = 'rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100';
@endphp

{{-- The Users and Accounts Manager sidebar (around this component) picks the panel and signatory section.
     Every create, edit and assign form opens in <x-admin.form-modal>, driven by $form. --}}
<div class="space-y-4">

        {{-- ============ ORGANIZATIONS ============ --}}
        @if ($panel === 'organizations')
            <x-admin.alert :message="session('org_message')" />

            <div class="{{ $toolbar }}">
                <p class="text-sm text-gray-500">{{ $organizations->total() }} {{ Str::plural('organization', $organizations->total()) }}</p>
                <button type="button" wire:click="openForm('org')" class="{{ $addBtn }}"><i class='bx bx-plus text-lg' aria-hidden="true"></i>Add organization</button>
            </div>

            <div class="{{ $filterBar }}">
                <div class="relative min-w-0 cq-md:flex-1">
                    <i class='bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400' aria-hidden="true"></i>
                    <input type="search" wire:model.live.debounce.300ms="filterOrgSearch" placeholder="Search organizations" aria-label="Search organizations" class="{{ $filterSearch }}">
                </div>
                <div class="{{ $filterRow }}">
                    <select wire:model.live="filterOrgCollege" aria-label="College" class="{{ $pill }}">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $c) <option value="{{ $c->id }}">{{ $c->code }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterOrgProgram" aria-label="Program" class="{{ $pill }} max-w-[180px]">
                        <option value="">All programs</option>
                        @foreach ($filterPrograms as $p) <option value="{{ $p->id }}">{{ $p->name }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterOrgStatus" aria-label="Status" class="{{ $pill }}">
                        <option value="">Any status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <button type="button" wire:click="clearFilters('org')" class="{{ $clearBtn }}">Clear</button>
                </div>
            </div>

            <div class="{{ $card }} overflow-hidden">
                <div class="{{ $wideTable }}">
                    <table class="w-full text-sm">
                        <thead class="{{ $thead }}">
                            <tr>
                                <th class="px-4 py-3 text-left">Organization</th>
                                <th class="hidden px-4 py-3 text-left cq-lg:table-cell">Program</th>
                                <th class="px-4 py-3 text-center">Members</th>
                                <th class="px-4 py-3 text-center">Officers</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($organizations as $org)
                                <tr wire:key="org-{{ $org->id }}">
                                    <td class="max-w-[220px] px-4 py-3 font-medium text-gray-900">
                                        <div class="truncate" title="{{ $org->name }}">{{ $org->name }}</div>
                                        @if ($org->year_level)
                                            <span class="rounded-full bg-purple-50 px-1.5 py-0.5 text-[10px] text-purple-600">
                                                {{ $org->year_level }}{{ ['', 'st', 'nd', 'rd', 'th'][$org->year_level] ?? 'th' }} year
                                            </span>
                                        @elseif (! ($org->is_academic ?? true))
                                            <span class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-500">Non-academic</span>
                                        @endif
                                    </td>
                                    <td class="hidden max-w-[240px] px-4 py-3 text-gray-600 cq-lg:table-cell">
                                        @forelse ($org->programs->take(2) as $prog)
                                            <div class="truncate text-xs" title="{{ $prog->name }}">
                                                <span class="rounded bg-blue-50 px-1 py-0.5 text-[10px] text-[#2A57B4]">{{ $prog->college->code ?? '—' }}</span>
                                                {{ $prog->name }}
                                            </div>
                                        @empty
                                            @if ($org->year_level)
                                                <span class="text-xs text-gray-400">By year level</span>
                                            @elseif (! ($org->is_academic ?? true))
                                                <span class="text-xs text-gray-400">None needed</span>
                                            @else
                                                <span class="text-xs text-amber-600">Unassigned</span>
                                            @endif
                                        @endforelse
                                        @if ($org->programs->count() > 2)
                                            <span class="text-[10px] text-gray-400" title="{{ $org->programs->skip(2)->pluck('name')->implode(', ') }}">
                                                +{{ $org->programs->count() - 2 }} more
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $org->members_count }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $org->officers_count }}</td>
                                    <td class="px-4 py-3">
                                        <button wire:click="toggleOrganizationActive({{ $org->id }})"
                                            class="rounded-full px-2 py-1 text-xs {{ $org->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $org->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="space-x-2 whitespace-nowrap px-4 py-3 text-right">
                                        <button wire:click="editOrganization({{ $org->id }})" class="px-1.5 py-2 text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                        <button wire:click="deleteOrganization({{ $org->id }})" wire:confirm="Remove this organization?" class="px-1.5 py-2 text-xs font-medium text-red-600 hover:underline">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No organizations match your filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <ul class="{{ $narrowList }}">
                    @forelse ($organizations as $org)
                        @php
                            $orgPrograms = match (true) {
                                (bool) $org->year_level => $org->year_level . (['', 'st', 'nd', 'rd', 'th'][$org->year_level] ?? 'th') . ' year organization',
                                ! ($org->is_academic ?? true) => 'Non-academic',
                                $org->programs->isEmpty() => 'No programs yet',
                                default => $org->programs->first()->name . ($org->programs->count() > 1 ? ' +' . ($org->programs->count() - 1) . ' more' : ''),
                            };
                        @endphp
                        <li wire:key="org-m-{{ $org->id }}" class="px-4 py-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900">{{ $org->name }}</p>
                                    <p class="truncate text-xs {{ $org->programs->isEmpty() && ($org->is_academic ?? true) && ! $org->year_level ? 'text-amber-600' : 'text-gray-500' }}">{{ $orgPrograms }}</p>
                                    <p class="text-xs text-gray-400">{{ $org->members_count }} members · {{ $org->officers_count }} officers</p>
                                </div>
                                <button wire:click="toggleOrganizationActive({{ $org->id }})"
                                    class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $org->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $org->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </div>
                            <div class="mt-2.5 flex justify-end gap-2">
                                <button wire:click="editOrganization({{ $org->id }})" class="{{ $rowBtn }}">Edit</button>
                                <button wire:click="deleteOrganization({{ $org->id }})" wire:confirm="Remove this organization?" class="{{ $rowBtnDanger }}">Delete</button>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-10 text-center text-sm text-gray-400">No organizations match your filters.</li>
                    @endforelse
                </ul>
                @if ($organizations->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">{{ $organizations->links() }}</div>
                @endif
            </div>

            @if ($form === 'org')
                <x-admin.form-modal :title="$editingOrgId ? 'Edit organization' : 'Add organization'" submit="saveOrganization" width="sm:max-w-2xl">
                    <div class="space-y-4">
                        <div>
                            <label class="{{ $label }}">Name</label>
                            <input type="text" wire:model="orgName" class="{{ $formInput }}">
                            @error('orgName') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $label }}">Year level organization (optional)</label>
                            <select wire:model.live="orgYearLevel" class="{{ $formInput }}">
                                <option value="">Not a year level organization</option>
                                <option value="1">1st year (Freshmen)</option>
                                <option value="2">2nd year (Sophomore)</option>
                                <option value="3">3rd year (Junior)</option>
                                <option value="4">4th year (Senior)</option>
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                Matched to students by their year level only. It has no programs and is never picked in a student's profile.
                            </p>
                            @error('orgYearLevel') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>

                        @if ($orgYearLevel)
                            <div class="rounded-lg bg-purple-50 px-3 py-2 text-xs text-purple-700">
                                This is a year level organization, so it is automatically non-academic and needs no programs.
                            </div>
                        @else
                            <label class="flex items-start gap-2 text-sm text-gray-700">
                                <input type="checkbox" wire:model.live="orgIsAcademic" class="mt-0.5 rounded border-gray-300 text-[#2A57B4]">
                                <span>Academic organization (tied to programs).<br>
                                <span class="text-xs text-gray-400">Untick for non-academic. No programs needed, and its adviser signs by emailed link.</span></span>
                            </label>
                        @endif

                        @if ($orgIsAcademic && ! $orgYearLevel)
                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="{{ $label }}">
                                        Programs
                                        <span class="font-normal text-gray-400">({{ count($orgProgramIds) }} selected)</span>
                                    </label>
                                    @if (count($orgProgramIds))
                                        <button type="button" wire:click="clearPrograms" class="text-[11px] text-gray-500 hover:text-red-600">Clear all</button>
                                    @endif
                                </div>

                                @if ($this->selectedPrograms->isNotEmpty())
                                    <div class="mt-1 flex max-h-24 flex-wrap gap-1 overflow-y-auto rounded-md bg-blue-50/60 p-1.5">
                                        @foreach ($this->selectedPrograms as $sp)
                                            <span wire:key="chip-{{ $sp->id }}"
                                                class="inline-flex max-w-full items-center gap-1 rounded-full border border-blue-200 bg-white py-0.5 pl-2 pr-1 text-[11px] text-[#2A57B4]">
                                                <span class="max-w-[170px] truncate" title="{{ $sp->name }}">
                                                    <strong>{{ $sp->college->code ?? '—' }}</strong> {{ $sp->name }}
                                                </span>
                                                <button type="button" wire:click="removeProgram({{ $sp->id }})"
                                                        class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-gray-400 hover:bg-red-50 hover:text-red-600"
                                                        title="Remove" aria-label="Remove {{ $sp->name }}">×</button>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-2 space-y-1.5">
                                    <input type="text" wire:model.live.debounce.300ms="pickerSearch" placeholder="Search programs…" class="w-full {{ $input }}">
                                    <div class="flex gap-1.5">
                                        <select wire:model.live="pickerCollege" class="flex-1 {{ $input }}">
                                            <option value="">All colleges</option>
                                            @foreach ($colleges as $c)
                                                <option value="{{ $c->id }}">{{ $c->code }}</option>
                                            @endforeach
                                        </select>
                                        <select wire:model.live="pickerView" class="flex-1 {{ $input }}">
                                            <option value="all">Show all</option>
                                            <option value="selected">Selected only</option>
                                            <option value="available">Available only</option>
                                        </select>
                                    </div>
                                    <button type="button" wire:click="selectVisiblePrograms" class="text-[11px] text-[#2A57B4] hover:underline">
                                        Select all visible available
                                    </button>
                                </div>

                                <div class="mt-1.5 max-h-64 space-y-2 overflow-y-auto rounded-md border border-gray-300 p-2">
                                    @php $grouped = $this->pickerPrograms->groupBy(fn ($p) => $p->college->code ?? 'No college'); @endphp

                                    @forelse ($grouped as $code => $items)
                                        <div wire:key="grp-{{ $code }}">
                                            <p class="sticky top-0 bg-white text-[11px] font-semibold text-[#2A57B4]">
                                                {{ $code }} <span class="font-normal text-gray-400">({{ $items->count() }})</span>
                                            </p>
                                            @foreach ($items as $p)
                                                @php $takenBy = $takenPrograms[$p->id] ?? null; @endphp
                                                <label wire:key="prog-opt-{{ $p->id }}"
                                                    class="flex items-start gap-2 py-1.5 text-sm cq-sm:py-0.5 {{ $takenBy ? 'cursor-not-allowed text-gray-400' : 'cursor-pointer text-gray-700' }}">
                                                    <input type="checkbox" wire:model.live="orgProgramIds" value="{{ $p->id }}"
                                                        @disabled($takenBy)
                                                        class="mt-0.5 rounded border-gray-300 text-[#2A57B4] focus:ring-[#2A57B4] disabled:bg-gray-100">
                                                    <span>
                                                        {{ $p->name }}
                                                        @unless ($p->is_active)
                                                            <span class="text-[10px] text-gray-400">(inactive)</span>
                                                        @endunless
                                                        @if ($takenBy)
                                                            <span class="block text-[10px] text-amber-600">Already used by {{ Str::limit($takenBy, 30) }}</span>
                                                        @endif
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @empty
                                        <p class="py-3 text-center text-xs text-gray-400">No programs match.</p>
                                    @endforelse
                                </div>

                                @error('orgProgramIds') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="{{ $label }}">Description</label>
                            <textarea wire:model="orgDescription" rows="3" class="{{ $formInput }}"></textarea>
                        </div>
                    </div>

                    <x-slot:footer>
                        <button type="button" wire:click="closeForm" class="{{ $secondaryBtn }}">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveOrganization" class="{{ $primaryBtn }}">{{ $editingOrgId ? 'Save changes' : 'Add organization' }}</button>
                    </x-slot:footer>
                </x-admin.form-modal>
            @endif
        @endif

        {{-- ============ PROGRAMS ============ --}}
        @if ($panel === 'programs')
            <x-admin.alert :message="session('program_message')" />

            <div class="{{ $toolbar }}">
                <p class="text-sm text-gray-500">{{ $programs->total() }} {{ Str::plural('program', $programs->total()) }}</p>
                <button type="button" wire:click="openForm('program')" class="{{ $addBtn }}"><i class='bx bx-plus text-lg' aria-hidden="true"></i>Add program</button>
            </div>

            <div class="{{ $filterBar }}">
                <div class="relative min-w-0 cq-md:flex-1">
                    <i class='bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400' aria-hidden="true"></i>
                    <input type="search" wire:model.live.debounce.300ms="filterProgSearch" placeholder="Search programs" aria-label="Search programs" class="{{ $filterSearch }}">
                </div>
                <div class="{{ $filterRow }}">
                    <select wire:model.live="filterProgCollege" aria-label="College" class="{{ $pill }}">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $c) <option value="{{ $c->id }}">{{ $c->code }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterProgStatus" aria-label="Status" class="{{ $pill }}">
                        <option value="">Any status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <button type="button" wire:click="clearFilters('prog')" class="{{ $clearBtn }}">Clear</button>
                </div>
            </div>

            <div class="{{ $card }} overflow-hidden">
                <div class="{{ $wideTable }}">
                    <table class="w-full text-sm">
                        <thead class="{{ $thead }}">
                            <tr>
                                <th class="px-4 py-3 text-left">Program</th>
                                <th class="px-4 py-3 text-left">College</th>
                                <th class="px-4 py-3 text-center">Orgs</th>
                                <th class="px-4 py-3 text-center">Students</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($programs as $program)
                                <tr wire:key="prog-{{ $program->id }}">
                                    <td class="max-w-[260px] px-4 py-3 font-medium text-gray-900">
                                        <div class="truncate" title="{{ $program->name }}">{{ $program->name }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($program->college)
                                            <span class="rounded-full bg-blue-50 px-1.5 py-0.5 text-[10px] text-[#2A57B4]">{{ $program->college->code }}</span>
                                        @else
                                            <span class="text-xs text-amber-600">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $program->organizations_count }}</td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $program->students_count }}</td>
                                    <td class="px-4 py-3">
                                        <button wire:click="toggleProgramActive({{ $program->id }})"
                                            class="rounded-full px-2 py-1 text-xs {{ $program->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }}">
                                            {{ $program->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="space-x-2 whitespace-nowrap px-4 py-3 text-right">
                                        <button wire:click="editProgram({{ $program->id }})" class="px-1.5 py-2 text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                        <button wire:click="deleteProgram({{ $program->id }})" wire:confirm="Remove this program?" class="px-1.5 py-2 text-xs font-medium text-red-600 hover:underline">Delete</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No programs match your filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <ul class="{{ $narrowList }}">
                    @forelse ($programs as $program)
                        <li wire:key="prog-m-{{ $program->id }}" class="px-4 py-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-gray-900">{{ $program->name }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">
                                        @if ($program->college)
                                            <span class="rounded-full bg-blue-50 px-1.5 py-0.5 text-[10px] text-[#2A57B4]">{{ $program->college->code }}</span>
                                        @else
                                            <span class="text-amber-600">No college</span>
                                        @endif
                                        <span class="text-gray-400">· {{ $program->organizations_count }} orgs · {{ $program->students_count }} students</span>
                                    </p>
                                </div>
                                <button wire:click="toggleProgramActive({{ $program->id }})"
                                    class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $program->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $program->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </div>
                            <div class="mt-2.5 flex justify-end gap-2">
                                <button wire:click="editProgram({{ $program->id }})" class="{{ $rowBtn }}">Edit</button>
                                <button wire:click="deleteProgram({{ $program->id }})" wire:confirm="Remove this program?" class="{{ $rowBtnDanger }}">Delete</button>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-10 text-center text-sm text-gray-400">No programs match your filters.</li>
                    @endforelse
                </ul>
                @if ($programs->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">{{ $programs->links() }}</div>
                @endif
            </div>

            @if ($form === 'program')
                <x-admin.form-modal :title="$editingProgramId ? 'Edit program' : 'Add program'" submit="saveProgram">
                    <div class="space-y-4">
                        <div>
                            <label class="{{ $label }}">Program name</label>
                            <input type="text" wire:model="programName" placeholder="e.g. Bachelor of Science in Information Technology" class="{{ $formInput }}">
                            @error('programName') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">College</label>
                            <select wire:model="programCollegeId" class="{{ $formInput }}">
                                <option value="">Select college…</option>
                                @foreach ($colleges as $college)
                                    <option value="{{ $college->id }}">{{ $college->code }} — {{ $college->name }}</option>
                                @endforeach
                            </select>
                            @error('programCollegeId') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <x-slot:footer>
                        <button type="button" wire:click="closeForm" class="{{ $secondaryBtn }}">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveProgram" class="{{ $primaryBtn }}">{{ $editingProgramId ? 'Save changes' : 'Add program' }}</button>
                    </x-slot:footer>
                </x-admin.form-modal>
            @endif
        @endif

        {{-- ============ OFFICER POSITIONS ============ --}}
        @if ($panel === 'positions')
            <x-admin.alert :message="session('position_message')" />

            <div class="{{ $toolbar }}">
                <p class="text-sm text-gray-500">{{ $positions->count() }} {{ Str::plural('position', $positions->count()) }}</p>
                <button type="button" wire:click="openForm('position')" class="{{ $addBtn }}"><i class='bx bx-plus text-lg' aria-hidden="true"></i>Add position</button>
            </div>

            <div class="{{ $card }} overflow-hidden">
                <div class="{{ $wideTable }}">
                <table class="w-full text-sm">
                    <thead class="{{ $thead }}">
                        <tr>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-center">Currently held</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($positions as $position)
                            <tr wire:key="pos-{{ $position->id }}">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $position->title }}</td>
                                <td class="px-4 py-3 text-center text-gray-600">{{ $position->officers_count }}</td>
                                <td class="space-x-2 whitespace-nowrap px-4 py-3 text-right">
                                    <button wire:click="editPosition({{ $position->id }})" class="px-1.5 py-2 text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                    <button wire:click="deletePosition({{ $position->id }})" wire:confirm="Remove this position?" class="px-1.5 py-2 text-xs font-medium text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-gray-400">No positions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
                <ul class="{{ $narrowList }}">
                    @forelse ($positions as $position)
                        <li wire:key="pos-m-{{ $position->id }}" class="flex items-center justify-between gap-3 px-4 py-3.5">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900">{{ $position->title }}</p>
                                <p class="text-xs text-gray-400">Held by {{ $position->officers_count }} {{ Str::plural('officer', $position->officers_count) }}</p>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="editPosition({{ $position->id }})" class="{{ $rowBtn }}">Edit</button>
                                <button wire:click="deletePosition({{ $position->id }})" wire:confirm="Remove this position?" class="{{ $rowBtnDanger }}">Delete</button>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-gray-400">No positions yet.</li>
                    @endforelse
                </ul>
            </div>

            @if ($form === 'position')
                <x-admin.form-modal :title="$editingPositionId ? 'Edit position' : 'Add officer position'" submit="savePosition" width="sm:max-w-md">
                    <div>
                        <label class="{{ $label }}">Title</label>
                        <input type="text" wire:model="positionTitle" placeholder="e.g. President, Auditor" class="{{ $formInput }}">
                        @error('positionTitle') <p class="{{ $error }}">{{ $message }}</p> @enderror
                    </div>

                    <x-slot:footer>
                        <button type="button" wire:click="closeForm" class="{{ $secondaryBtn }}">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="savePosition" class="{{ $primaryBtn }}">{{ $editingPositionId ? 'Save changes' : 'Add position' }}</button>
                    </x-slot:footer>
                </x-admin.form-modal>
            @endif
        @endif

        {{-- ============ ASSIGN OFFICERS ============ --}}
        @if ($panel === 'officers')
            <x-admin.alert :message="session('assign_message')" />

            <div class="{{ $toolbar }}">
                <p class="text-sm text-gray-500">{{ $officers->total() }} {{ Str::plural('officer', $officers->total()) }}</p>
                <button type="button" wire:click="openForm('officer')" class="{{ $addBtn }}"><i class='bx bx-user-plus text-lg' aria-hidden="true"></i>Assign officer</button>
            </div>

            <div class="{{ $filterBar }}">
                <div class="relative min-w-0 cq-md:flex-1">
                    <i class='bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400' aria-hidden="true"></i>
                    <input type="search" wire:model.live.debounce.300ms="filterOffSearch" placeholder="Search name or student number" aria-label="Search officers" class="{{ $filterSearch }}">
                </div>
                <div class="{{ $filterRow }}">
                    <select wire:model.live="filterOffCollege" aria-label="College" class="{{ $pill }}">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $c) <option value="{{ $c->id }}">{{ $c->code }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterOffOrg" aria-label="Organization" class="{{ $pill }} max-w-[180px]">
                        <option value="">All organizations</option>
                        @foreach ($filterOrgs as $o) <option value="{{ $o->id }}">{{ Str::limit($o->name, 30) }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterOffPosition" aria-label="Position" class="{{ $pill }}">
                        <option value="">All positions</option>
                        @foreach ($positions as $p) <option value="{{ $p->id }}">{{ $p->title }}</option> @endforeach
                    </select>
                    <button type="button" wire:click="clearFilters('off')" class="{{ $clearBtn }}">Clear</button>
                </div>
            </div>

            <div class="{{ $card }} overflow-hidden">
                <div class="{{ $wideTable }}">
                    <table class="w-full text-sm">
                        <thead class="{{ $thead }}">
                            <tr>
                                <th class="px-4 py-3 text-left">Student</th>
                                <th class="px-4 py-3 text-left">Organization</th>
                                <th class="px-4 py-3 text-left">Position</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($officers as $officer)
                                @php $studentName = trim(($officer->user->first_name ?? '') . ' ' . ($officer->user->last_name ?? '')); @endphp
                                <tr wire:key="off-{{ $officer->id }}">
                                    <td class="max-w-[200px] px-4 py-3">
                                        <div class="truncate font-medium text-gray-900" title="{{ $studentName }}">{{ $studentName }}</div>
                                        <div class="text-xs text-gray-400">{{ $officer->user->student_number ?? '' }}</div>
                                    </td>
                                    <td class="max-w-[220px] px-4 py-3 text-gray-600">
                                        <div class="truncate" title="{{ $officer->organization->name ?? '—' }}">{{ $officer->organization->name ?? '—' }}</div>
                                        <div class="truncate text-xs text-gray-400">{{ $officer->organization->programs->pluck('name')->implode(', ') }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="whitespace-nowrap rounded-full bg-amber-50 px-2 py-0.5 text-[11px] text-amber-600">{{ $officer->position->title ?? '—' }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <button wire:click="removeOfficer({{ $officer->id }})" wire:confirm="Remove this officer role?" class="px-1.5 py-2 text-xs font-medium text-red-600 hover:underline">Remove</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-4 py-10 text-center text-gray-400">No officers match your filters.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <ul class="{{ $narrowList }}">
                    @forelse ($officers as $officer)
                        <li wire:key="off-m-{{ $officer->id }}" class="px-4 py-3.5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-gray-900">{{ trim(($officer->user->first_name ?? '') . ' ' . ($officer->user->last_name ?? '')) }}</p>
                                    <p class="text-xs text-gray-400">{{ $officer->user->student_number ?? '' }}</p>
                                    <p class="mt-0.5 truncate text-xs text-gray-600">{{ $officer->organization->name ?? '—' }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] text-amber-600">{{ $officer->position->title ?? '—' }}</span>
                            </div>
                            <div class="mt-2.5 flex justify-end">
                                <button wire:click="removeOfficer({{ $officer->id }})" wire:confirm="Remove this officer role?" class="{{ $rowBtnDanger }}">Remove</button>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-10 text-center text-sm text-gray-400">No officers match your filters.</li>
                    @endforelse
                </ul>
                @if ($officers->hasPages())
                    <div class="border-t border-gray-100 px-4 py-3">{{ $officers->links() }}</div>
                @endif
            </div>

            @if ($form === 'officer')
                <x-admin.form-modal title="Make a student an officer"
                    subtitle="Assigning locks the student's college, program and organization on their profile until the role is removed."
                    submit="assignOfficer">
                    <div class="space-y-4">
                        <div>
                            <label class="{{ $label }}">1. Organization</label>
                            <select wire:model.live="assignOrganizationId" class="{{ $formInput }}">
                                <option value="">Select…</option>
                                @foreach ($orgOptions as $org)
                                    <option value="{{ $org->id }}">{{ Str::limit($org->name, 40) }}</option>
                                @endforeach
                            </select>
                            @error('assignOrganizationId') <p class="{{ $error }}">{{ $message }}</p> @enderror

                            @if ($this->assignOrganization)
                                <div class="mt-2 rounded-lg bg-blue-50 px-3 py-2 text-xs text-[#2A57B4]">
                                    Eligible: students of
                                    <strong>{{ $this->assignOrganization->programs->pluck('name')->implode(', ') }}</strong>
                                    <span class="text-blue-400">({{ $this->eligibleCount }} available)</span>
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="{{ $label }}">2. Student</label>

                            @if ($selectedStudent)
                                <div class="mt-1 flex items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm">
                                    <span class="truncate">
                                        {{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }}
                                        <span class="text-xs text-gray-500">({{ $selectedStudent->student_number }})</span>
                                    </span>
                                    <button type="button" wire:click="$set('selectedStudentId', null)" class="shrink-0 text-xs font-semibold text-gray-500 hover:text-gray-800">Change</button>
                                </div>
                            @else
                                <input type="text" wire:model.live.debounce.300ms="studentSearch"
                                    @disabled(! $assignOrganizationId)
                                    placeholder="{{ $assignOrganizationId ? 'Search by name or student number' : 'Select an organization first' }}"
                                    class="{{ $formInput }} disabled:bg-gray-100">

                                @if ($assignOrganizationId && strlen($studentSearch) >= 2)
                                    <div class="mt-1 max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white">
                                        @forelse ($this->studentResults as $student)
                                            <button type="button" wire:click="selectStudent({{ $student->id }})" wire:key="stu-{{ $student->id }}"
                                                class="flex w-full items-center gap-1 px-3 py-2.5 text-left text-sm hover:bg-gray-50">
                                                <span class="truncate">{{ $student->first_name }} {{ $student->last_name }}</span>
                                                <span class="shrink-0 text-xs text-gray-400">({{ $student->student_number }})</span>
                                            </button>
                                        @empty
                                            <div class="px-3 py-2 text-sm text-gray-400">No eligible students found.</div>
                                        @endforelse
                                    </div>
                                @endif
                            @endif
                            @error('selectedStudentId') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $label }}">3. Officer position</label>
                            <select wire:model="assignPositionId" class="{{ $formInput }}">
                                <option value="">Select…</option>
                                @foreach ($positions as $position)
                                    <option value="{{ $position->id }}">{{ Str::limit($position->title, 40) }}</option>
                                @endforeach
                            </select>
                            @error('assignPositionId') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <x-slot:footer>
                        <button type="button" wire:click="closeForm" class="{{ $secondaryBtn }}">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="assignOfficer" class="{{ $primaryBtn }}">Assign officer</button>
                    </x-slot:footer>
                </x-admin.form-modal>
            @endif
        @endif

        {{-- ============ SIGNATORIES ============ --}}
        @if ($panel === 'signatories')
            @php
                $activeRoles = $signatoryRoles->where('is_active', true);
                $rolesWithoutHolder = $activeRoles->filter(fn ($r) => ($sigHolderCounts[$r->id] ?? 0) === 0);
                $scopeText = fn ($s) => ['organization' => 'Per organization', 'program' => 'Per program', 'college' => 'Per college'][$s] ?? 'Everyone';
                $selRole = $signatoryRoles->firstWhere('id', (int) $sigRoleId);
                $invRole = $signatoryRoles->firstWhere('id', (int) $invRoleId);
                $today = \Illuminate\Support\Carbon::today();
            @endphp

            {{-- The section comes from the sidebar (?sig=). Jumps inside the page (e.g. "Review roles")
                 keep the URL and the sidebar highlight in step. "Add a person" is a modal, not a section. --}}
            <div x-data="{ tab: @js($sigTab), coverage: false }"
                 x-init="$watch('tab', v => {
                     const url = new URL(window.location);
                     v === 'people' ? url.searchParams.delete('sig') : url.searchParams.set('sig', v);
                     history.replaceState(history.state, '', url);
                     $dispatch('users-manager-nav', 'sig-' + v);
                 })"
                 class="space-y-4">

                @foreach (['sig_role_message', 'sig_assign_message', 'sig_invite_message'] as $flashKey)
                    <x-admin.alert :message="session($flashKey)" class="!mb-0" />
                @endforeach

                @if ($rolesWithoutHolder->isNotEmpty())
                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900">
                        <span>
                            <strong>{{ $rolesWithoutHolder->count() }} {{ Str::plural('role', $rolesWithoutHolder->count()) }} without a signer.</strong>
                            Documents that need them cannot be sent for signing.
                        </span>
                        <button type="button" @click="tab = 'people'; coverage = true"
                                class="text-xs font-semibold text-amber-800 underline hover:text-amber-950">Review roles</button>
                    </div>
                @endif

                {{-- ===== WHO CAN SIGN ===== --}}
                <div x-show="tab === 'people'" x-cloak class="space-y-4">

                    <div class="{{ $toolbar }}">
                        <p class="text-sm text-gray-500">{{ $signatoryAssignments->total() }} {{ Str::plural('assignment', $signatoryAssignments->total()) }}</p>
                        <button type="button" wire:click="openForm('signatory')" class="{{ $addBtn }}"><i class='bx bx-user-plus text-lg' aria-hidden="true"></i>Add a person</button>
                    </div>

                    {{-- Role coverage: collapsed by default so the table is visible straight away --}}
                    <div class="{{ $card }}">
                        <button type="button" @click="coverage = !coverage" :aria-expanded="coverage"
                                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left">
                            <span class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                                <span class="font-semibold text-gray-900">Role coverage</span>
                                <span class="text-gray-500">{{ $activeRoles->count() - $rolesWithoutHolder->count() }} of {{ $activeRoles->count() }} roles have a signer</span>
                                @if ($rolesWithoutHolder->isNotEmpty())
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">{{ $rolesWithoutHolder->count() }} need a signer</span>
                                @endif
                            </span>
                            <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="coverage ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                        </button>

                        <div x-show="coverage" x-cloak class="space-y-4 border-t border-gray-100 px-4 py-4">
                            @if ($rolesWithoutHolder->isNotEmpty())
                                <div>
                                    <p class="mb-2 text-xs font-medium text-gray-500">Needs a signer. Select a role to assign someone.</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($rolesWithoutHolder as $r)
                                            <button type="button" wire:key="need-{{ $r->id }}"
                                                    wire:click="openForm('signatory', {{ $r->id }})"
                                                    title="{{ $scopeText($r->scope) }}"
                                                    class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100">
                                                + {{ $r->label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @php $coveredRoles = $activeRoles->reject(fn ($r) => ($sigHolderCounts[$r->id] ?? 0) === 0); @endphp
                            @if ($coveredRoles->isNotEmpty())
                                <div>
                                    <p class="mb-2 text-xs font-medium text-gray-500">Has a signer. Select a role to filter the table.</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($coveredRoles as $r)
                                            <button type="button" wire:key="cov-{{ $r->id }}"
                                                    wire:click="$set('filterSigRole', '{{ $r->id }}')"
                                                    @click="coverage = false"
                                                    title="{{ $scopeText($r->scope) }}"
                                                    class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                                {{ $r->label }}
                                                <span class="rounded-full bg-emerald-50 px-1.5 text-[11px] font-semibold text-emerald-700">{{ $sigHolderCounts[$r->id] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($activeRoles->isEmpty())
                                <p class="text-sm text-gray-500">No roles yet. Open <strong>Roles</strong> to create the first one.</p>
                            @endif
                        </div>
                    </div>

                    <div class="{{ $filterBar }}">
                        <div class="relative min-w-0 cq-md:flex-1">
                            <i class='bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400' aria-hidden="true"></i>
                            <input type="search" wire:model.live.debounce.300ms="filterSigSearch" placeholder="Search name or email" aria-label="Search signatories" class="{{ $filterSearch }}">
                        </div>
                        <div class="{{ $filterRow }}">
                            <select wire:model.live="filterSigRole" aria-label="Role" class="{{ $pill }} max-w-[180px]">
                                <option value="">All roles</option>
                                @foreach ($signatoryRoles as $r) <option value="{{ $r->id }}">{{ $r->label }}</option> @endforeach
                            </select>
                            <select wire:model.live="filterSigStatus" aria-label="Status" class="{{ $pill }}">
                                <option value="">Any status</option>
                                <option value="current">Can sign now</option>
                                <option value="upcoming">Starts later</option>
                                <option value="expired">Expired</option>
                                <option value="inactive">Ended</option>
                            </select>
                            <button type="button" wire:click="clearFilters('sig')" class="{{ $clearBtn }}">Clear</button>
                        </div>
                    </div>

                    <div class="{{ $card }} overflow-hidden">
                        <div class="{{ $wideTable }}">
                            <table class="w-full text-sm">
                                <thead class="{{ $thead }}">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Person</th>
                                        <th class="px-4 py-3 text-left">Signs as</th>
                                        <th class="hidden px-4 py-3 text-left cq-lg:table-cell">Period</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($signatoryAssignments as $assignment)
                                        @php
                                            $isUpcoming = $assignment->is_active && $assignment->active_from && $assignment->active_from->gt($today);
                                            $isExpired = $assignment->is_active && $assignment->active_until && $assignment->active_until->lt($today);

                                            [$statusLabel, $statusClass] = match (true) {
                                                ! $assignment->is_active => ['Ended', 'bg-gray-100 text-gray-500'],
                                                $isUpcoming => ['Starts ' . $assignment->active_from->format('M j'), 'bg-blue-50 text-blue-600'],
                                                $isExpired => ['Expired', 'bg-amber-50 text-amber-700'],
                                                default => ['Can sign now', 'bg-emerald-50 text-emerald-700'],
                                            };

                                            $personName = trim(($assignment->user->first_name ?? '') . ' ' . ($assignment->user->last_name ?? '')) ?: ('User #' . $assignment->user_id);
                                            $initials = strtoupper(substr($assignment->user->first_name ?? '?', 0, 1) . substr($assignment->user->last_name ?? '', 0, 1));
                                        @endphp
                                        <tr wire:key="sigassign-{{ $assignment->id }}" class="{{ $assignment->is_active && ! $isExpired ? '' : 'bg-gray-50/60 text-gray-500' }}">
                                            <td class="px-4 py-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-xs font-bold text-[#2A57B4]">{{ $initials }}</span>
                                                    <div class="min-w-0 max-w-[200px]">
                                                        <div class="truncate font-semibold text-gray-900" title="{{ $personName }}">{{ $personName }}</div>
                                                        <div class="truncate text-xs text-gray-400">{{ $assignment->user->email ?? '' }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="font-medium text-gray-800">{{ $assignment->role->label ?? '-' }}</div>
                                                @if ($assignment->scope_name)
                                                    <div class="text-[11px] text-gray-400">for {{ $assignment->scope_name }}</div>
                                                @endif
                                                <div class="text-[11px] text-gray-400 cq-lg:hidden">{{ $assignment->active_from?->format('M j, Y') ?? 'Now' }} &rarr; {{ $assignment->active_until?->format('M j, Y') ?? 'Ongoing' }}</div>
                                            </td>
                                            <td class="hidden whitespace-nowrap px-4 py-3 text-xs text-gray-500 cq-lg:table-cell">
                                                @if (! $assignment->active_from && ! $assignment->active_until)
                                                    No end date
                                                @else
                                                    {{ $assignment->active_from?->format('M j, Y') ?? 'Now' }} &rarr; {{ $assignment->active_until?->format('M j, Y') ?? 'Ongoing' }}
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                @if ($assignment->is_active)
                                                    <button wire:click="endSignatoryAssignment({{ $assignment->id }})"
                                                            wire:confirm="Stop this person from signing as {{ $assignment->role->label ?? 'this role' }}?"
                                                            class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100">Stop</button>
                                                @else
                                                    <button wire:click="reactivateSignatoryAssignment({{ $assignment->id }})"
                                                            class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Restore</button>
                                                @endif
                                                <button wire:click="deleteSignatoryAssignment({{ $assignment->id }})"
                                                        wire:confirm="Permanently delete this record? Use Stop instead if you only want them to stop signing."
                                                        class="ml-1 px-1.5 py-1 text-xs text-gray-400 hover:text-red-600" title="Delete record" aria-label="Delete record">
                                                    <i class='bx bx-trash'></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400">
                                                No one matches. Try clearing the filters, or use <strong>Add a person</strong>.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <ul class="{{ $narrowList }}">
                            @forelse ($signatoryAssignments as $assignment)
                                @php
                                    $isUpcoming = $assignment->is_active && $assignment->active_from && $assignment->active_from->gt($today);
                                    $isExpired = $assignment->is_active && $assignment->active_until && $assignment->active_until->lt($today);
                                    [$statusLabel, $statusClass] = match (true) {
                                        ! $assignment->is_active => ['Ended', 'bg-gray-100 text-gray-500'],
                                        $isUpcoming => ['Starts ' . $assignment->active_from->format('M j'), 'bg-blue-50 text-blue-600'],
                                        $isExpired => ['Expired', 'bg-amber-50 text-amber-700'],
                                        default => ['Can sign now', 'bg-emerald-50 text-emerald-700'],
                                    };
                                    $personName = trim(($assignment->user->first_name ?? '') . ' ' . ($assignment->user->last_name ?? '')) ?: ('User #' . $assignment->user_id);
                                    $initials = strtoupper(substr($assignment->user->first_name ?? '?', 0, 1) . substr($assignment->user->last_name ?? '', 0, 1));
                                @endphp
                                <li wire:key="sigassign-m-{{ $assignment->id }}" class="px-4 py-3.5 {{ $assignment->is_active && ! $isExpired ? '' : 'bg-gray-50/60' }}">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#2A57B4]/10 text-xs font-bold text-[#2A57B4]">{{ $initials }}</span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <p class="min-w-0 truncate font-semibold text-gray-900">{{ $personName }}</p>
                                                <span class="shrink-0 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $statusClass }}">{{ $statusLabel }}</span>
                                            </div>
                                            <p class="truncate text-xs text-gray-400">{{ $assignment->user->email ?? '' }}</p>
                                            <p class="mt-1 text-sm font-medium text-gray-800">
                                                {{ $assignment->role->label ?? '-' }}
                                                @if ($assignment->scope_name)<span class="text-xs font-normal text-gray-400">for {{ $assignment->scope_name }}</span>@endif
                                            </p>
                                            <p class="text-[11px] text-gray-400">{{ $assignment->active_from?->format('M j, Y') ?? 'Now' }} &rarr; {{ $assignment->active_until?->format('M j, Y') ?? 'Ongoing' }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-2.5 flex justify-end gap-2">
                                        @if ($assignment->is_active)
                                            <button wire:click="endSignatoryAssignment({{ $assignment->id }})"
                                                    wire:confirm="Stop this person from signing as {{ $assignment->role->label ?? 'this role' }}?"
                                                    class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-100">Stop</button>
                                        @else
                                            <button wire:click="reactivateSignatoryAssignment({{ $assignment->id }})"
                                                    class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100">Restore</button>
                                        @endif
                                        <button wire:click="deleteSignatoryAssignment({{ $assignment->id }})"
                                                wire:confirm="Permanently delete this record? Use Stop instead if you only want them to stop signing."
                                                class="{{ $rowBtnDanger }}">Delete</button>
                                    </div>
                                </li>
                            @empty
                                <li class="px-4 py-12 text-center text-sm text-gray-400">No one matches. Try clearing the filters, or use <strong>Add a person</strong>.</li>
                            @endforelse
                        </ul>
                        @if ($signatoryAssignments->hasPages())
                            <div class="border-t border-gray-100 px-4 py-3">{{ $signatoryAssignments->links() }}</div>
                        @endif
                    </div>
                </div>

                {{-- ===== ROLES ===== --}}
                <div x-show="tab === 'roles'" x-cloak class="space-y-4">
                    <div class="{{ $toolbar }}">
                        <p class="min-w-0 text-sm text-gray-500">Documents ask for roles, never for named people.</p>
                        <button type="button" wire:click="openForm('role')" class="{{ $addBtn }}"><i class='bx bx-plus text-lg' aria-hidden="true"></i>Create role</button>
                    </div>

                    <div class="{{ $card }} overflow-hidden">
                        <div class="divide-y divide-gray-100">
                            @forelse ($signatoryRoles as $r)
                                @php $n = $sigHolderCounts[$r->id] ?? 0; @endphp
                                <div wire:key="sigrole-{{ $r->id }}"
                                    class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 {{ $r->is_active ? '' : 'opacity-60' }}">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-semibold text-gray-900">{{ $r->label }}</p>
                                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-[#2A57B4]">{{ $scopeText($r->scope) }}</span>
                                        </div>
                                        <p class="font-mono text-[11px] text-gray-400">{{ $r->key }}</p>
                                        <p class="mt-0.5 text-[11px] font-medium {{ $n > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                                            {{ $n > 0 ? $n . ' ' . Str::plural('person', $n) . ' can sign' : 'Nobody assigned yet' }}
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-2">
                                        <button wire:click="toggleSignatoryRoleActive({{ $r->id }})"
                                                class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $r->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}"
                                                title="Click to turn {{ $r->is_active ? 'off' : 'on' }}">
                                            {{ $r->is_active ? 'On' : 'Off' }}
                                        </button>
                                        <button wire:click="editSignatoryRole({{ $r->id }})" class="text-xs font-semibold text-blue-600 hover:underline">Edit</button>
                                        <button wire:click="deleteSignatoryRole({{ $r->id }})" wire:confirm="Delete this role?" class="text-xs text-gray-400 hover:text-red-600" title="Delete" aria-label="Delete role">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <p class="px-4 py-10 text-center text-sm text-gray-400">No roles yet. Use <strong>Create role</strong> to add the first one.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- ===== INVITATIONS ===== --}}
                <div x-show="tab === 'invites'" x-cloak class="space-y-4">
                    <div class="{{ $toolbar }}">
                        <p class="min-w-0 text-sm text-gray-500">Resend the link if it expired or they lost it.</p>
                        <button type="button" wire:click="openForm('signatory', null, 'invite')" class="{{ $addBtn }}"><i class='bx bx-envelope text-lg' aria-hidden="true"></i>Invite someone</button>
                    </div>

                    <div class="{{ $card }} overflow-hidden">
                        <div class="{{ $wideTable }}">
                            <table class="w-full text-sm">
                                <thead class="{{ $thead }}">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Person</th>
                                        <th class="px-4 py-3 text-left">Status</th>
                                        <th class="px-4 py-3 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($invitations as $inv)
                                        @php
                                            [$text, $cls] = match (true) {
                                                (bool) $inv->used_at => ['Account ready', 'bg-emerald-50 text-emerald-700'],
                                                (bool) $inv->declined_at => ['Declined', 'bg-rose-50 text-rose-700'],
                                                (bool) $inv->cancelled_at => ['Cancelled', 'bg-gray-100 text-gray-500'],
                                                $inv->expires_at && $inv->expires_at->isPast() => ['Link expired', 'bg-amber-50 text-amber-700'],
                                                default => ['Waiting for them', 'bg-blue-50 text-blue-600'],
                                            };
                                            $closed = $inv->declined_at || $inv->cancelled_at;
                                            $open = ! $inv->used_at && ! $closed;
                                        @endphp
                                        <tr wire:key="inv-{{ $inv->id }}">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-900">{{ trim(($inv->user->first_name ?? '') . ' ' . ($inv->user->last_name ?? '')) }}</div>
                                                <div class="text-xs text-gray-400">{{ $inv->user->email ?? '' }}</div>
                                            </td>
                                            <td class="px-4 py-3"><span class="whitespace-nowrap rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $cls }}">{{ $text }}</span></td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                @unless ($inv->used_at)
                                                    <button wire:click="resendInvitation({{ $inv->user_id }})"
                                                            wire:confirm="Email a new setup link? Older links stop working."
                                                            class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-semibold text-[#2A57B4] hover:bg-blue-50">
                                                        {{ $closed ? 'Invite again' : 'Resend link' }}
                                                    </button>
                                                @endunless
                                                @if ($open)
                                                    <button wire:click="cancelInvitation({{ $inv->id }})"
                                                            wire:confirm="Cancel this invitation? The link will stop working."
                                                            class="ml-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100">
                                                        Cancel
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-4 py-12 text-center text-sm text-gray-400">No invitations yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <ul class="{{ $narrowList }}">
                            @forelse ($invitations as $inv)
                                @php
                                    [$text, $cls] = match (true) {
                                        (bool) $inv->used_at => ['Account ready', 'bg-emerald-50 text-emerald-700'],
                                        (bool) $inv->declined_at => ['Declined', 'bg-rose-50 text-rose-700'],
                                        (bool) $inv->cancelled_at => ['Cancelled', 'bg-gray-100 text-gray-500'],
                                        $inv->expires_at && $inv->expires_at->isPast() => ['Link expired', 'bg-amber-50 text-amber-700'],
                                        default => ['Waiting for them', 'bg-blue-50 text-blue-600'],
                                    };
                                    $closed = $inv->declined_at || $inv->cancelled_at;
                                    $open = ! $inv->used_at && ! $closed;
                                @endphp
                                <li wire:key="inv-m-{{ $inv->id }}" class="px-4 py-3.5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-gray-900">{{ trim(($inv->user->first_name ?? '') . ' ' . ($inv->user->last_name ?? '')) }}</p>
                                            <p class="truncate text-xs text-gray-400">{{ $inv->user->email ?? '' }}</p>
                                        </div>
                                        <span class="shrink-0 whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $cls }}">{{ $text }}</span>
                                    </div>
                                    @if (! $inv->used_at)
                                        <div class="mt-2.5 flex justify-end gap-2">
                                            <button wire:click="resendInvitation({{ $inv->user_id }})"
                                                    wire:confirm="Email a new setup link? Older links stop working."
                                                    class="{{ $rowBtn }}">{{ $closed ? 'Invite again' : 'Resend link' }}</button>
                                            @if ($open)
                                                <button wire:click="cancelInvitation({{ $inv->id }})"
                                                        wire:confirm="Cancel this invitation? The link will stop working."
                                                        class="{{ $rowBtnDanger }}">Cancel</button>
                                            @endif
                                        </div>
                                    @endif
                                </li>
                            @empty
                                <li class="px-4 py-12 text-center text-sm text-gray-400">No invitations yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            {{-- ===== ROLE MODAL ===== --}}
            @if ($form === 'role')
                <x-admin.form-modal :title="$editingSigRoleId ? 'Edit role' : 'Create a role'"
                    subtitle='A role is a signing position, like "Guidance Counselor". Documents ask for roles, never for named people.'
                    submit="saveSignatoryRole">
                    <div class="space-y-4">
                        <div>
                            <label class="{{ $label }}">Name shown to users</label>
                            <input type="text" wire:model="sigRoleLabel" placeholder="e.g. Guidance Counselor" class="{{ $formInput }}">
                            @error('sigRoleLabel') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Code</label>
                            <input type="text" wire:model="sigRoleKey" placeholder="guidance_counselor" class="{{ $formInput }} font-mono">
                            <p class="mt-1 text-[11px] text-gray-400">Lowercase letters, numbers and underscores. Forms use this code.</p>
                            @error('sigRoleKey') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Who does this role cover?</label>
                            <select wire:model.live="sigRoleScope" class="{{ $formInput }}">
                                <option value="none">Everyone (one shared pool)</option>
                                <option value="organization">Each organization has its own</option>
                                <option value="program">Each program has its own</option>
                                <option value="college">Each college has its own</option>
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">
                                @if ($sigRoleScope === 'none')
                                    Anyone holding this role can sign for any student. Best for the VP or Guidance Counselor.
                                @else
                                    Signers are picked per {{ $sigRoleScope }}, so each {{ $sigRoleScope }} needs its own signatory.
                                @endif
                            </p>
                            @error('sigRoleScope') <p class="{{ $error }}">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Notes (optional)</label>
                            <textarea wire:model="sigRoleDescription" rows="2" class="{{ $formInput }}"></textarea>
                        </div>
                    </div>

                    <x-slot:footer>
                        <button type="button" wire:click="closeForm" class="{{ $secondaryBtn }}">Cancel</button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveSignatoryRole" class="{{ $primaryBtn }}">{{ $editingSigRoleId ? 'Save changes' : 'Create role' }}</button>
                    </x-slot:footer>
                </x-admin.form-modal>
            @endif

            {{-- ===== ADD A PERSON MODAL ===== --}}
            @if ($form === 'signatory')
                <x-admin.form-modal title="Add a person" subtitle="Give an existing account a signing role, or invite someone new."
                    :state="'{ addMode: ' . Js::from($sigAddMode) . ' }'">
                    <div class="space-y-5">
                        <div class="flex w-full rounded-lg bg-gray-100 p-1">
                            <button type="button" @click="addMode = 'existing'"
                                    class="flex-1 rounded-md px-3 py-2 text-xs font-semibold transition sm:text-sm"
                                    :class="addMode === 'existing' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'">
                                Has an account
                            </button>
                            <button type="button" @click="addMode = 'invite'"
                                    class="flex-1 rounded-md px-3 py-2 text-xs font-semibold transition sm:text-sm"
                                    :class="addMode === 'invite' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'">
                                New person (invite)
                            </button>
                        </div>

                        {{-- Existing account --}}
                        <div x-show="addMode === 'existing'" class="space-y-5">
                            <div>
                                <p class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#2A57B4] text-[11px] text-white">1</span> Who?
                                </p>
                                @if ($sigSelectedUser)
                                    <div class="flex items-center justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm">
                                        <span class="min-w-0 truncate">
                                            <strong class="text-gray-900">{{ $sigSelectedUser->first_name }} {{ $sigSelectedUser->last_name }}</strong>
                                            @if ($sigSelectedUser->student_number)
                                                <span class="rounded bg-white px-1.5 py-0.5 font-mono text-[11px] text-gray-600">{{ $sigSelectedUser->student_number }}</span>
                                            @endif
                                            <span class="text-xs text-gray-500">{{ $sigSelectedUser->email }}</span>
                                        </span>
                                        <button type="button" wire:click="$set('sigSelectedUserId', null)" class="shrink-0 text-xs font-semibold text-gray-500 hover:text-gray-800">Change</button>
                                    </div>
                                @else
                                    <input type="text" wire:model.live.debounce.300ms="sigUserSearch"
                                        placeholder="Search by name, student number or email"
                                        class="{{ $input }} w-full">
                                    @if (strlen(trim($sigUserSearch)) >= 2)
                                        <div class="mt-1 max-h-56 overflow-y-auto rounded-lg border border-gray-200 bg-white">
                                            @forelse ($this->sigUserResults as $u)
                                                <button type="button" wire:click="selectSigUser({{ $u->id }})" wire:key="siguser-{{ $u->id }}"
                                                        class="flex w-full items-center justify-between gap-3 px-3.5 py-2.5 text-left text-sm hover:bg-gray-50">
                                                    <span class="min-w-0">
                                                        <span class="block truncate font-medium text-gray-900">{{ $u->first_name }} {{ $u->last_name }}</span>
                                                        <span class="block truncate text-xs text-gray-400">{{ $u->email }}</span>
                                                    </span>
                                                    @if ($u->student_number)
                                                        <span class="shrink-0 rounded bg-gray-100 px-1.5 py-0.5 font-mono text-[11px] text-gray-600">{{ $u->student_number }}</span>
                                                    @endif
                                                </button>
                                            @empty
                                                <p class="px-3.5 py-2 text-sm text-gray-400">No one found. Use "New person" if they have no account yet.</p>
                                            @endforelse
                                        </div>
                                    @endif
                                @endif
                                @error('sigSelectedUserId') <p class="{{ $error }} font-medium">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <p class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-900">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#2A57B4] text-[11px] text-white">2</span> Signs as
                                </p>
                                <select wire:model.live="sigRoleId" class="{{ $input }} w-full">
                                    <option value="">Choose a role…</option>
                                    @foreach ($activeRoles as $r)
                                        <option value="{{ $r->id }}">{{ $r->label }}</option>
                                    @endforeach
                                </select>
                                @error('sigRoleId') <p class="{{ $error }} font-medium">{{ $message }}</p> @enderror

                                @if ($selRole && $selRole->scope !== 'none')
                                    <div class="mt-3" wire:key="sig-scope-{{ $selRole->id }}">
                                        <label class="{{ $label }}">Which {{ $selRole->scope }} do they sign for?</label>
                                        <select wire:model="sigScopeId" class="{{ $formInput }}">
                                            <option value="">Choose…</option>
                                            @foreach ($this->scopeOptions($selRole->scope) as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error('sigScopeId') <p class="{{ $error }} font-medium">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>

                            <details class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                                <summary class="cursor-pointer font-semibold text-gray-700">Limit to certain dates (optional)</summary>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="{{ $label }}">Starts</label>
                                        <input type="date" wire:model="sigActiveFrom" class="{{ $formInput }}">
                                    </div>
                                    <div>
                                        <label class="{{ $label }}">Ends</label>
                                        <input type="date" wire:model="sigActiveUntil" class="{{ $formInput }}">
                                        @error('sigActiveUntil') <p class="{{ $error }} font-medium">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">Leave blank to start right away with no end date.</p>
                            </details>
                        </div>

                        {{-- Invite --}}
                        <div x-show="addMode === 'invite'" x-cloak class="space-y-4">
                            <div class="rounded-lg bg-amber-50 px-4 py-3 text-xs text-amber-900">
                                For staff who only sign. <strong>Confirm who they are first.</strong>
                                They get an email to choose their own password.
                            </div>

                            @error('invite') <p class="rounded-lg bg-red-50 px-3 py-2 text-xs font-medium text-red-700">{{ $message }}</p> @enderror

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="{{ $label }}">First name</label>
                                    <input type="text" wire:model="invFirst" class="{{ $formInput }}">
                                    @error('invFirst') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="{{ $label }}">Last name</label>
                                    <input type="text" wire:model="invLast" class="{{ $formInput }}">
                                    @error('invLast') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="{{ $label }}">Official email</label>
                                <input type="email" wire:model.live.debounce.500ms="invEmail" placeholder="name@lnu.edu.ph" class="{{ $formInput }}">
                                @error('invEmail') <p class="{{ $error }}">{{ $message }}</p> @enderror

                                @if ($invEmailMatch)
                                    <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-900">
                                        @if ($invEmailMatch['trashed'])
                                            This email belongs to a <strong>removed</strong> account ({{ $invEmailMatch['name'] }}). Restore it under Manage Accounts first.
                                        @else
                                            <strong>{{ $invEmailMatch['name'] }}</strong> already has an account ({{ $invEmailMatch['role'] }}).
                                            <button type="button" class="font-semibold text-[#2A57B4] underline"
                                                    @click="$wire.useExistingForInvite().then(() => addMode = 'existing')">
                                                Assign them as a signatory instead
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            <div>
                                <label class="{{ $label }}">Signs as</label>
                                <select wire:model.live="invRoleId" class="{{ $formInput }}">
                                    <option value="">Choose a role…</option>
                                    @foreach ($activeRoles as $r)
                                        <option value="{{ $r->id }}">{{ $r->label }}</option>
                                    @endforeach
                                </select>
                                @error('invRoleId') <p class="{{ $error }}">{{ $message }}</p> @enderror
                            </div>
                            @if ($invRole && $invRole->scope !== 'none')
                                <div wire:key="inv-scope-{{ $invRole->id }}">
                                    <label class="{{ $label }}">Which {{ $invRole->scope }}?</label>
                                    <select wire:model="invScopeId" class="{{ $formInput }}">
                                        <option value="">Choose…</option>
                                        @foreach ($this->scopeOptions($invRole->scope) as $id => $name)
                                            <option value="{{ $id }}">{{ $name }}</option>
                                        @endforeach
                                    </select>
                                    @error('invScopeId') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            <details class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                                <summary class="cursor-pointer font-semibold text-gray-700">Limit to certain dates (optional)</summary>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="{{ $label }}">Starts</label>
                                        <input type="date" wire:model="invFrom" class="{{ $formInput }}">
                                    </div>
                                    <div>
                                        <label class="{{ $label }}">Ends</label>
                                        <input type="date" wire:model="invUntil" class="{{ $formInput }}">
                                        @error('invUntil') <p class="{{ $error }}">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <x-slot:footer>
                        <button type="button" wire:click="closeForm" class="{{ $secondaryBtn }}">Cancel</button>
                        <button type="button" x-show="addMode === 'existing'" wire:click="assignSignatory" wire:loading.attr="disabled" wire:target="assignSignatory"
                                class="{{ $primaryBtn }}">Assign signatory</button>
                        <button type="button" x-show="addMode === 'invite'" x-cloak wire:click="inviteSignatory" wire:loading.attr="disabled" wire:target="inviteSignatory"
                                @disabled($invEmailMatch) class="{{ $primaryBtn }}">
                            <span wire:loading.remove wire:target="inviteSignatory">Send invitation</span>
                            <span wire:loading wire:target="inviteSignatory">Sending…</span>
                        </button>
                    </x-slot:footer>
                </x-admin.form-modal>
            @endif
        @endif

</div>
