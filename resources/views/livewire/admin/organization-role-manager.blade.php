@php
    $input = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';
    $formInput = 'mt-1 w-full ' . $input;
    $label = 'block text-xs font-semibold text-gray-500 uppercase';
    $tabs = [
        'organizations' => 'Organizations',
        'programs' => 'Programs',
        'positions' => 'Officer Positions',
        'officers' => 'Assign Officers',
        'signatories' => 'Signatories',
    ];
@endphp

<div class="space-y-6">

    {{-- Sub-nav with counts --}}
    <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3">
        @foreach ($tabs as $key => $text)
            <button type="button" wire:click="$set('panel', '{{ $key }}')"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition
                    {{ $panel === $key ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $text }}
                <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $panel === $key ? 'bg-white/20' : 'bg-white text-gray-500' }}">
                    {{ $counts[$key] }}
                </span>
            </button>
        @endforeach
    </div>

        {{-- ============ ORGANIZATIONS ============ --}}
    @if ($panel === 'organizations')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm h-fit">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $editingOrgId ? 'Edit Organization' : 'Add Organization' }}</h3>

                @if (session('org_message'))
                    <div class="mb-3 text-sm rounded-lg bg-blue-50 text-blue-700 px-3 py-2">{{ session('org_message') }}</div>
                @endif

                <form wire:submit="saveOrganization" class="space-y-3">
                    <div>
                        <label class="{{ $label }}">Name</label>
                        <input type="text" wire:model="orgName" class="{{ $formInput }}">
                        @error('orgName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Year level comes first: choosing one makes the org independent of programs --}}
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
                        @error('orgYearLevel') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
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
                                    <span class="normal-case font-normal text-gray-400">({{ count($orgProgramIds) }} selected)</span>
                                </label>
                                @if (count($orgProgramIds))
                                    <button type="button" wire:click="clearPrograms" class="text-[11px] text-gray-500 hover:text-red-600">Clear all</button>
                                @endif
                            </div>

                            {{-- Selected chips: click × to unlink --}}
                            @if ($this->selectedPrograms->isNotEmpty())
                                <div class="mt-1 flex flex-wrap gap-1 max-h-24 overflow-y-auto rounded-md bg-blue-50/60 p-1.5">
                                    @foreach ($this->selectedPrograms as $sp)
                                        <span wire:key="chip-{{ $sp->id }}"
                                            class="inline-flex items-center gap-1 rounded-full bg-white border border-blue-200 pl-2 pr-1 py-0.5 text-[11px] text-[#2A57B4] max-w-full">
                                            <span class="truncate max-w-[170px]" title="{{ $sp->name }}">
                                                <strong>{{ $sp->college->code ?? '—' }}</strong> {{ $sp->name }}
                                            </span>
                                            <button type="button" wire:click="removeProgram({{ $sp->id }})"
                                                    class="shrink-0 rounded-full w-4 h-4 flex items-center justify-center text-gray-400 hover:bg-red-50 hover:text-red-600"
                                                    title="Remove">×</button>
                                        </span>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Search + filters --}}
                            <div class="mt-2 space-y-1.5">
                                <input type="text" wire:model.live.debounce.300ms="pickerSearch" placeholder="Search programs…"
                                    class="w-full {{ $input }}">
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

                            {{-- Checklist grouped by college --}}
                            <div class="mt-1.5 max-h-64 overflow-y-auto rounded-md border border-gray-300 p-2 space-y-2">
                                @php $grouped = $this->pickerPrograms->groupBy(fn ($p) => $p->college->code ?? 'No college'); @endphp

                                @forelse ($grouped as $code => $items)
                                    <div wire:key="grp-{{ $code }}">
                                        <p class="sticky top-0 bg-white text-[10px] font-bold uppercase tracking-wide text-[#2A57B4]">
                                            {{ $code }} <span class="text-gray-400 font-normal">({{ $items->count() }})</span>
                                        </p>
                                        @foreach ($items as $p)
                                            @php $takenBy = $takenPrograms[$p->id] ?? null; @endphp
                                            <label wire:key="prog-opt-{{ $p->id }}"
                                                class="flex items-start gap-2 py-0.5 text-sm {{ $takenBy ? 'text-gray-400 cursor-not-allowed' : 'text-gray-700 cursor-pointer' }}">
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

                            @error('orgProgramIds') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="{{ $label }}">Description</label>
                        <textarea wire:model="orgDescription" rows="3" class="{{ $formInput }}"></textarea>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            {{ $editingOrgId ? 'Save Changes' : 'Add Organization' }}
                        </button>
                        @if ($editingOrgId)
                            <button type="button" wire:click="resetOrganizationForm" class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 space-y-3">
                {{-- Filters --}}
                <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm flex flex-wrap gap-2">
                    <input type="text" wire:model.live.debounce.300ms="filterOrgSearch" placeholder="Search organizations…" class="flex-1 min-w-[160px] {{ $input }}">
                    <select wire:model.live="filterOrgCollege" class="{{ $input }}">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $c) <option value="{{ $c->id }}">{{ $c->code }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterOrgProgram" class="max-w-[200px] {{ $input }}">
                        <option value="">All programs</option>
                        @foreach ($filterPrograms as $p) <option value="{{ $p->id }}">{{ $p->name }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterOrgStatus" class="{{ $input }}">
                        <option value="">Any status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <button type="button" wire:click="clearFilters('org')" class="text-xs text-gray-500 hover:text-gray-800 px-2">Clear</button>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
                                <tr>
                                    <th class="px-4 py-3 text-left">Organization</th>
                                    <th class="px-4 py-3 text-left">Program</th>
                                    <th class="px-4 py-3 text-center">Members</th>
                                    <th class="px-4 py-3 text-center">Officers</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($organizations as $org)
                                    <tr wire:key="org-{{ $org->id }}">
                                        <td class="px-4 py-3 font-medium text-gray-900 max-w-[220px]">
                                            <div class="truncate" title="{{ $org->name }}">{{ $org->name }}</div>
                                            @if ($org->year_level)
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-purple-50 text-purple-600">
                                                    {{ $org->year_level }}{{ ['', 'st', 'nd', 'rd', 'th'][$org->year_level] ?? 'th' }} year
                                                </span>
                                            @elseif (! ($org->is_academic ?? true))
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">Non-academic</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 max-w-[240px]">
                                            @forelse ($org->programs->take(2) as $prog)
                                                <div class="truncate text-xs" title="{{ $prog->name }}">
                                                    <span class="text-[10px] px-1 py-0.5 rounded bg-blue-50 text-[#2A57B4]">{{ $prog->college->code ?? '—' }}</span>
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
                                                class="text-xs px-2 py-1 rounded-full {{ $org->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $org->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                            <button wire:click="editOrganization({{ $org->id }})" class="text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                            <button wire:click="deleteOrganization({{ $org->id }})" wire:confirm="Remove this organization?" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No organizations match your filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($organizations->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">{{ $organizations->links() }}</div>
                    @endif
                </div>
                <p class="text-xs text-gray-400">{{ $organizations->total() }} result(s)</p>
            </div>
        </div>
    @endif

    {{-- ============ PROGRAMS ============ --}}
    @if ($panel === 'programs')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm h-fit">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $editingProgramId ? 'Edit Program' : 'Add Program' }}</h3>

                @if (session('program_message'))
                    <div class="mb-3 text-sm rounded-lg bg-blue-50 text-blue-700 px-3 py-2">{{ session('program_message') }}</div>
                @endif

                <form wire:submit="saveProgram" class="space-y-3">
                    <div>
                        <label class="{{ $label }}">Program Name</label>
                        <input type="text" wire:model="programName" placeholder="e.g. Bachelor of Science in Information Technology" class="{{ $formInput }}">
                        @error('programName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">College</label>
                        <select wire:model="programCollegeId" class="{{ $formInput }}">
                            <option value="">Select college…</option>
                            @foreach ($colleges as $college)
                                <option value="{{ $college->id }}">{{ $college->code }} — {{ $college->name }}</option>
                            @endforeach
                        </select>
                        @error('programCollegeId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            {{ $editingProgramId ? 'Save Changes' : 'Add Program' }}
                        </button>
                        @if ($editingProgramId)
                            <button type="button" wire:click="resetProgramForm" class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 space-y-3">
                <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm flex flex-wrap gap-2">
                    <input type="text" wire:model.live.debounce.300ms="filterProgSearch" placeholder="Search programs…" class="flex-1 min-w-[160px] {{ $input }}">
                    <select wire:model.live="filterProgCollege" class="{{ $input }}">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $c) <option value="{{ $c->id }}">{{ $c->code }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterProgStatus" class="{{ $input }}">
                        <option value="">Any status</option>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <button type="button" wire:click="clearFilters('prog')" class="text-xs text-gray-500 hover:text-gray-800 px-2">Clear</button>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
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
                                        <td class="px-4 py-3 font-medium text-gray-900 max-w-[260px]">
                                            <div class="truncate" title="{{ $program->name }}">{{ $program->name }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($program->college)
                                                <span class="text-[10px] px-1.5 py-0.5 rounded-full bg-blue-50 text-[#2A57B4]">{{ $program->college->code }}</span>
                                            @else
                                                <span class="text-xs text-amber-600">Unassigned</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center text-gray-600">{{ $program->organizations_count }}</td>
                                        <td class="px-4 py-3 text-center text-gray-600">{{ $program->students_count }}</td>
                                        <td class="px-4 py-3">
                                            <button wire:click="toggleProgramActive({{ $program->id }})"
                                                class="text-xs px-2 py-1 rounded-full {{ $program->is_active ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $program->is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                            <button wire:click="editProgram({{ $program->id }})" class="text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                            <button wire:click="deleteProgram({{ $program->id }})" wire:confirm="Remove this program?" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No programs match your filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($programs->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">{{ $programs->links() }}</div>
                    @endif
                </div>
                <p class="text-xs text-gray-400">{{ $programs->total() }} result(s)</p>
            </div>
        </div>
    @endif

    {{-- ============ OFFICER POSITIONS ============ --}}
    @if ($panel === 'positions')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm h-fit">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">{{ $editingPositionId ? 'Edit Position' : 'Add Officer Position' }}</h3>

                @if (session('position_message'))
                    <div class="mb-3 text-sm rounded-lg bg-blue-50 text-blue-700 px-3 py-2">{{ session('position_message') }}</div>
                @endif

                <form wire:submit="savePosition" class="space-y-3">
                    <div>
                        <label class="{{ $label }}">Title</label>
                        <input type="text" wire:model="positionTitle" placeholder="e.g. President, Auditor" class="{{ $formInput }}">
                        @error('positionTitle') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            {{ $editingPositionId ? 'Save Changes' : 'Add Position' }}
                        </button>
                        @if ($editingPositionId)
                            <button type="button" wire:click="resetPositionForm" class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
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
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    <button wire:click="editPosition({{ $position->id }})" class="text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                    <button wire:click="deletePosition({{ $position->id }})" wire:confirm="Remove this position?" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">No positions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============ ASSIGN OFFICERS ============ --}}
    @if ($panel === 'officers')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Assign form --}}
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm h-fit">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Make a Student an Officer</h3>

                @if (session('assign_message'))
                    <div class="mb-3 text-sm rounded-lg bg-blue-50 text-blue-700 px-3 py-2">{{ session('assign_message') }}</div>
                @endif

                <div class="space-y-4">
                    {{-- 1. Organization --}}
                    <div>
                        <label class="{{ $label }}">1. Organization</label>
                        <select wire:model.live="assignOrganizationId" class="{{ $formInput }}">
                            <option value="">Select…</option>
                            @foreach ($orgOptions as $org)
                                <option value="{{ $org->id }}">{{ Str::limit($org->name, 40) }}</option>
                            @endforeach
                        </select>
                        @error('assignOrganizationId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                        @if ($this->assignOrganization)
                            <div class="mt-2 rounded-lg bg-blue-50 px-3 py-2 text-xs text-[#2A57B4]">
                                Eligible: students of
                                <strong>{{ $this->assignOrganization->programs->pluck('name')->implode(', ') }}</strong>
                                <span class="text-blue-400">({{ $this->eligibleCount }} available)</span>
                            </div>
                        @endif
                    </div>

                    {{-- 2. Student --}}
                    <div class="relative">
                        <label class="{{ $label }}">2. Student</label>

                        @if ($selectedStudent)
                            <div class="mt-1 flex items-center justify-between gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                <span class="truncate">
                                    {{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }}
                                    <span class="text-xs text-gray-400">({{ $selectedStudent->student_number }})</span>
                                </span>
                                <button type="button" wire:click="$set('selectedStudentId', null)" class="shrink-0 text-gray-400 hover:text-gray-700">✕</button>
                            </div>
                        @else
                            <input type="text" wire:model.live.debounce.300ms="studentSearch"
                                @disabled(! $assignOrganizationId)
                                placeholder="{{ $assignOrganizationId ? 'Search by name or student number' : 'Select an organization first' }}"
                                class="{{ $formInput }} disabled:bg-gray-100">

                            @if ($assignOrganizationId && strlen($studentSearch) >= 2)
                                <div class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow max-h-64 overflow-y-auto">
                                    @forelse ($this->studentResults as $student)
                                        <div wire:click="selectStudent({{ $student->id }})" wire:key="stu-{{ $student->id }}"
                                            class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-1">
                                            <span class="truncate">{{ $student->first_name }} {{ $student->last_name }}</span>
                                            <span class="text-xs text-gray-400 shrink-0">({{ $student->student_number }})</span>
                                        </div>
                                    @empty
                                        <div class="px-3 py-2 text-sm text-gray-400">No eligible students found.</div>
                                    @endforelse
                                </div>
                            @endif
                        @endif
                        @error('selectedStudentId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- 3. Position --}}
                    <div>
                        <label class="{{ $label }}">3. Officer Position</label>
                        <select wire:model="assignPositionId" class="{{ $formInput }}">
                            <option value="">Select…</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ Str::limit($position->title, 40) }}</option>
                            @endforeach
                        </select>
                        @error('assignPositionId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="button" wire:click="assignOfficer" class="w-full rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Assign Officer
                    </button>
                    <p class="text-xs text-gray-400">Assigning locks the student's college, program and organization on their profile until the role is removed.</p>
                </div>
            </div>

            {{-- Officers list --}}
            <div class="lg:col-span-2 space-y-3">
                <div class="bg-white border border-gray-200 rounded-2xl p-3 shadow-sm flex flex-wrap gap-2">
                    <input type="text" wire:model.live.debounce.300ms="filterOffSearch" placeholder="Search name or student no…" class="flex-1 min-w-[160px] {{ $input }}">
                    <select wire:model.live="filterOffCollege" class="{{ $input }}">
                        <option value="">All colleges</option>
                        @foreach ($colleges as $c) <option value="{{ $c->id }}">{{ $c->code }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterOffOrg" class="max-w-[180px] {{ $input }}">
                        <option value="">All organizations</option>
                        @foreach ($filterOrgs as $o) <option value="{{ $o->id }}">{{ Str::limit($o->name, 30) }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterOffPosition" class="{{ $input }}">
                        <option value="">All positions</option>
                        @foreach ($positions as $p) <option value="{{ $p->id }}">{{ $p->title }}</option> @endforeach
                    </select>
                    <button type="button" wire:click="clearFilters('off')" class="text-xs text-gray-500 hover:text-gray-800 px-2">Clear</button>
                </div>

                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
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
                                        <td class="px-4 py-3 max-w-[200px]">
                                            <div class="font-medium text-gray-900 truncate" title="{{ $studentName }}">{{ $studentName }}</div>
                                            <div class="text-xs text-gray-400">{{ $officer->user->student_number ?? '' }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 max-w-[220px]">
                                            <div class="truncate" title="{{ $officer->organization->name ?? '—' }}">{{ $officer->organization->name ?? '—' }}</div>
                                            <div class="text-xs text-gray-400 truncate">{{ $officer->organization->programs->pluck('name')->implode(', ') }}</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">{{ $officer->position->title ?? '—' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap">
                                            <button wire:click="removeOfficer({{ $officer->id }})" wire:confirm="Remove this officer role?" class="text-xs font-medium text-red-600 hover:underline">Remove</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">No officers match your filters.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($officers->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">{{ $officers->links() }}</div>
                    @endif
                </div>
                <p class="text-xs text-gray-400">{{ $officers->total() }} officer(s)</p>
            </div>
        </div>
    @endif

   {{-- ============ SIGNATORIES (redesigned) ============
     Replace the whole old `@if ($panel === 'signatories') ... @endif` block with this file's contents.
     No PHP changes needed. Uses $label, $input, $formInput defined at the top of the main file. --}}
    @if ($panel === 'signatories')
        @php
            $activeRoles = $signatoryRoles->where('is_active', true);
            $rolesWithoutHolder = $activeRoles->filter(fn ($r) => ($sigHolderCounts[$r->id] ?? 0) === 0);
            $pendingInvites = $invitations->filter(fn ($i) => ! $i->used_at && ! $i->declined_at && ! $i->cancelled_at)->count();
            $scopeText = fn ($s) => ['organization' => 'Per organization', 'program' => 'Per program', 'college' => 'Per college'][$s] ?? 'Everyone';
            $selRole = $signatoryRoles->firstWhere('id', (int) $sigRoleId);
            $invRole = $signatoryRoles->firstWhere('id', (int) $invRoleId);
            $today = \Illuminate\Support\Carbon::today();
        @endphp

        <div x-data="{ tab: 'people', addMode: 'existing' }" class="space-y-5">

            {{-- Messages --}}
            @foreach (['sig_role_message', 'sig_assign_message', 'sig_invite_message'] as $flashKey)
                @if (session($flashKey))
                    <div class="flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                        <i class='bx bx-info-circle mt-0.5 text-lg'></i>
                        <span>{{ session($flashKey) }}</span>
                    </div>
                @endif
            @endforeach

            {{-- Needs attention --}}
            @if ($rolesWithoutHolder->isNotEmpty())
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <div class="flex items-start gap-2 text-sm text-amber-900">
                        <i class='bx bx-error mt-0.5 text-lg'></i>
                        <span>
                            <strong>Nobody can sign for:</strong>
                            {{ $rolesWithoutHolder->pluck('label')->implode(', ') }}.
                            Documents that need these roles cannot be sent for signing.
                        </span>
                    </div>
                    <button type="button" @click="tab = 'add'; addMode = 'existing'"
                            class="shrink-0 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                        Assign someone
                    </button>
                </div>
            @endif

            {{-- Tabs --}}
            <div class="flex flex-wrap gap-1 rounded-2xl border border-gray-200 bg-white p-1.5 shadow-sm">
                @foreach ([
                    'people' => ['Who can sign', 'bx-pen', null],
                    'add' => ['Add a person', 'bx-user-plus', null],
                    'roles' => ['Roles', 'bx-id-card', $signatoryRoles->count()],
                    'invites' => ['Invitations', 'bx-envelope', $pendingInvites ?: null],
                ] as $key => [$text, $icon, $badge])
                    <button type="button" @click="tab = '{{ $key }}'"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                            :class="tab === '{{ $key }}' ? 'bg-[#2A57B4] text-white shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'">
                        <i class='bx {{ $icon }}'></i>
                        {{ $text }}
                        @if ($badge)
                            <span class="rounded-full bg-white/20 px-1.5 text-[10px] font-bold"
                                :class="tab === '{{ $key }}' ? '' : '!bg-amber-100 !text-amber-700'">{{ $badge }}</span>
                        @endif
                    </button>
                @endforeach
            </div>

            {{-- =====================================================
                TAB 1: WHO CAN SIGN
            ===================================================== --}}
            <div x-show="tab === 'people'" x-cloak class="space-y-4">

                {{-- One card per role: instant health check --}}
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($activeRoles as $r)
                        @php $n = $sigHolderCounts[$r->id] ?? 0; @endphp
                        <div wire:key="rolecard-{{ $r->id }}"
                            class="rounded-2xl border p-4 shadow-sm {{ $n > 0 ? 'border-gray-200 bg-white' : 'border-amber-200 bg-amber-50/60' }}">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-bold text-gray-900" title="{{ $r->label }}">{{ $r->label }}</p>
                                    <p class="text-[11px] text-gray-400">{{ $scopeText($r->scope) }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $n > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $n > 0 ? $n . ' can sign' : 'No one' }}
                                </span>
                            </div>
                            <button type="button"
                                    wire:click="$set('sigRoleId', {{ $r->id }})"
                                    @click="tab = 'add'; addMode = 'existing'"
                                    class="mt-3 text-xs font-semibold text-[#2A57B4] hover:underline">
                                + Assign a person to this role
                            </button>
                        </div>
                    @empty
                        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                            No roles yet. Open the <strong>Roles</strong> tab to create the first one.
                        </div>
                    @endforelse
                </div>

                {{-- Filters --}}
                <div class="flex flex-wrap gap-2 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
                    <input type="text" wire:model.live.debounce.300ms="filterSigSearch" placeholder="Search name or email…"
                        class="min-w-[160px] flex-1 {{ $input }}">
                    <select wire:model.live="filterSigRole" class="max-w-[200px] {{ $input }}">
                        <option value="">All roles</option>
                        @foreach ($signatoryRoles as $r) <option value="{{ $r->id }}">{{ $r->label }}</option> @endforeach
                    </select>
                    <select wire:model.live="filterSigStatus" class="{{ $input }}">
                        <option value="">Any status</option>
                        <option value="current">Can sign now</option>
                        <option value="upcoming">Starts later</option>
                        <option value="expired">Expired</option>
                        <option value="inactive">Ended</option>
                    </select>
                    <button type="button" wire:click="clearFilters('sig')" class="px-2 text-xs text-gray-500 hover:text-gray-800">Clear</button>
                </div>

                {{-- Assignments --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
                                <tr>
                                    <th class="px-4 py-3 text-left">Person</th>
                                    <th class="px-4 py-3 text-left">Signs as</th>
                                    <th class="px-4 py-3 text-left">Period</th>
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
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-3 text-xs text-gray-500">
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
                                                    class="ml-1 px-1.5 py-1 text-xs text-gray-400 hover:text-red-600" title="Delete record">
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
                    @if ($signatoryAssignments->hasPages())
                        <div class="border-t border-gray-100 px-4 py-3">{{ $signatoryAssignments->links() }}</div>
                    @endif
                </div>
                <p class="text-xs text-gray-400">{{ $signatoryAssignments->total() }} assignment(s)</p>
            </div>

            {{-- =====================================================
                TAB 2: ADD A PERSON
            ===================================================== --}}
            <div x-show="tab === 'add'" x-cloak class="mx-auto max-w-2xl space-y-4">

                <div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 shadow-sm">
                    <button type="button" @click="addMode = 'existing'"
                            class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                            :class="addMode === 'existing' ? 'bg-[#2A57B4] text-white' : 'text-gray-500 hover:text-gray-800'">
                        They already have an account
                    </button>
                    <button type="button" @click="addMode = 'invite'"
                            class="rounded-lg px-4 py-2 text-sm font-semibold transition"
                            :class="addMode === 'invite' ? 'bg-[#2A57B4] text-white' : 'text-gray-500 hover:text-gray-800'">
                        New person (send invite)
                    </button>
                </div>

                {{-- Existing account --}}
                <div x-show="addMode === 'existing'" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">

                    {{-- Step 1 --}}
                    <div class="relative">
                        <p class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#2A57B4] text-[11px] text-white">1</span> Who?
                        </p>
                        @if ($sigSelectedUser)
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm">
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
                                class="{{ $input }} mt-0 w-full">
                            @if (strlen(trim($sigUserSearch)) >= 2)
                                <div class="absolute z-10 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-lg">
                                    @forelse ($this->sigUserResults as $u)
                                        <button type="button" wire:click="selectSigUser({{ $u->id }})" wire:key="siguser-{{ $u->id }}"
                                                class="flex w-full items-center justify-between gap-3 px-3.5 py-2 text-left text-sm hover:bg-gray-50">
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
                        @error('sigSelectedUserId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>

                    {{-- Step 2 --}}
                    <div>
                        <p class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <span class="flex h-5 w-5 items-center justify-center rounded-full bg-[#2A57B4] text-[11px] text-white">2</span> Signs as
                        </p>
                        <select wire:model.live="sigRoleId" class="{{ $input }} mt-0 w-full">
                            <option value="">Choose a role…</option>
                            @foreach ($activeRoles as $r)
                                <option value="{{ $r->id }}">{{ $r->label }}</option>
                            @endforeach
                        </select>
                        @error('sigRoleId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror

                        @if ($selRole && $selRole->scope !== 'none')
                            <div class="mt-3" wire:key="sig-scope-{{ $selRole->id }}">
                                <label class="text-xs font-semibold text-gray-600">Which {{ $selRole->scope }} do they sign for?</label>
                                <select wire:model="sigScopeId" class="{{ $input }} mt-1 w-full">
                                    <option value="">Choose…</option>
                                    @foreach ($this->scopeOptions($selRole->scope) as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('sigScopeId') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>

                    {{-- Step 3 --}}
                    <details class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                        <summary class="cursor-pointer font-semibold text-gray-700">Limit to certain dates (optional)</summary>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-600">Starts</label>
                                <input type="date" wire:model="sigActiveFrom" class="{{ $input }} mt-1 w-full">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600">Ends</label>
                                <input type="date" wire:model="sigActiveUntil" class="{{ $input }} mt-1 w-full">
                                @error('sigActiveUntil') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-400">Leave blank to start right away with no end date.</p>
                    </details>

                    <button type="button" wire:click="assignSignatory" wire:loading.attr="disabled"
                            @click="tab = 'people'"
                            class="w-full rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                        Assign signatory
                    </button>
                </div>

                {{-- Invite --}}
                <div x-show="addMode === 'invite'" x-cloak class="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                    <div class="rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-900">
                        For staff who only sign. <strong>Confirm who they are first.</strong>
                        They get an email to choose their own password.
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="{{ $label }}">First name</label>
                            <input type="text" wire:model="invFirst" class="{{ $formInput }}">
                            @error('invFirst') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Last name</label>
                            <input type="text" wire:model="invLast" class="{{ $formInput }}">
                            @error('invLast') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="{{ $label }}">Official email</label>
                        <input type="email" wire:model="invEmail" placeholder="name@lnu.edu.ph" class="{{ $formInput }}">
                        @error('invEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="{{ $label }}">Signs as</label>
                        <select wire:model.live="invRoleId" class="{{ $formInput }}">
                            <option value="">Choose a role…</option>
                            @foreach ($activeRoles as $r)
                                <option value="{{ $r->id }}">{{ $r->label }}</option>
                            @endforeach
                        </select>
                        @error('invRoleId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                            @error('invScopeId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <details class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                        <summary class="cursor-pointer font-semibold text-gray-700">Limit to certain dates (optional)</summary>
                        <div class="mt-3 grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-600">Starts</label>
                                <input type="date" wire:model="invFrom" class="{{ $input }} mt-1 w-full">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600">Ends</label>
                                <input type="date" wire:model="invUntil" class="{{ $input }} mt-1 w-full">
                                @error('invUntil') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </details>

                    <button type="button" wire:click="inviteSignatory" wire:loading.attr="disabled"
                            class="w-full rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                        <span wire:loading.remove wire:target="inviteSignatory">Send invitation</span>
                        <span wire:loading wire:target="inviteSignatory">Sending…</span>
                    </button>
                </div>
            </div>

            {{-- =====================================================
                TAB 3: ROLES
            ===================================================== --}}
            <div x-show="tab === 'roles'" x-cloak class="grid grid-cols-1 gap-5 lg:grid-cols-5">

                {{-- Role form --}}
                <div class="h-fit rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                    <h3 class="text-sm font-bold text-gray-900">{{ $editingSigRoleId ? 'Edit role' : 'Create a role' }}</h3>
                    <p class="mb-4 text-xs text-gray-500">A role is a signing position, like "Guidance Counselor". Documents ask for roles, never for named people.</p>

                    <form wire:submit="saveSignatoryRole" class="space-y-3">
                        <div>
                            <label class="{{ $label }}">Name shown to users</label>
                            <input type="text" wire:model="sigRoleLabel" placeholder="e.g. Guidance Counselor" class="{{ $formInput }}">
                            @error('sigRoleLabel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Code</label>
                            <input type="text" wire:model="sigRoleKey" placeholder="guidance_counselor" class="{{ $formInput }} font-mono">
                            <p class="mt-1 text-[11px] text-gray-400">Lowercase letters, numbers and underscores. Forms use this code.</p>
                            @error('sigRoleKey') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
                            @error('sigRoleScope') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="{{ $label }}">Notes (optional)</label>
                            <textarea wire:model="sigRoleDescription" rows="2" class="{{ $formInput }}"></textarea>
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button type="submit" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                {{ $editingSigRoleId ? 'Save changes' : 'Create role' }}
                            </button>
                            @if ($editingSigRoleId)
                                <button type="button" wire:click="resetSignatoryRoleForm" class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">Cancel</button>
                            @endif
                        </div>
                    </form>
                </div>

                {{-- Role list --}}
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm lg:col-span-3">
                    <div class="divide-y divide-gray-100">
                        @forelse ($signatoryRoles as $r)
                            @php $n = $sigHolderCounts[$r->id] ?? 0; @endphp
                            <div wire:key="sigrole-{{ $r->id }}"
                                class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 {{ $editingSigRoleId === $r->id ? 'bg-blue-50/60' : '' }} {{ $r->is_active ? '' : 'opacity-60' }}">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-semibold text-gray-900">{{ $r->label }}</p>
                                        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-[#2A57B4]">{{ $scopeText($r->scope) }}</span>
                                    </div>
                                    <p class="font-mono text-[11px] text-gray-400">{{ $r->key }}</p>
                                    <p class="mt-0.5 text-[11px] font-medium {{ $n > 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                                        {{ $n > 0 ? $n . ' ' . \Illuminate\Support\Str::plural('person', $n) . ' can sign' : 'Nobody assigned yet' }}
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <button wire:click="toggleSignatoryRoleActive({{ $r->id }})"
                                            class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $r->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}"
                                            title="Click to turn {{ $r->is_active ? 'off' : 'on' }}">
                                        {{ $r->is_active ? 'On' : 'Off' }}
                                    </button>
                                    <button wire:click="editSignatoryRole({{ $r->id }})" class="text-xs font-semibold text-blue-600 hover:underline">Edit</button>
                                    <button wire:click="deleteSignatoryRole({{ $r->id }})" wire:confirm="Delete this role?" class="text-xs text-gray-400 hover:text-red-600" title="Delete">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="px-4 py-10 text-center text-sm text-gray-400">No roles yet. Create one on the left.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- =====================================================
                TAB 4: INVITATIONS
            ===================================================== --}}
            <div x-show="tab === 'invites'" x-cloak class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-bold text-gray-900">Invited signatories</h3>
                    <p class="text-xs text-gray-500">People you invited to set up an account. Resend the link if it expired or they lost it.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
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
                                    <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $cls }}">{{ $text }}</span></td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <button wire:click="resendInvitation({{ $inv->user_id }})"
                                                wire:confirm="Email a new setup link? Older links stop working."
                                                class="rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-semibold text-[#2A57B4] hover:bg-blue-50">
                                            {{ $inv->used_at ? 'Reset password' : ($closed ? 'Invite again' : 'Resend link') }}
                                        </button>
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
            </div>
        </div>
    @endif
</div>
