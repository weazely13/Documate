@php
    $input = 'rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';
    $formInput = 'mt-1 w-full ' . $input;
    $label = 'block text-xs font-semibold text-gray-500 uppercase';
    $tabs = [
        'organizations' => 'Organizations',
        'programs' => 'Programs',
        'positions' => 'Officer Positions',
        'officers' => 'Assign Officers',
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
                                        </td>
                                        <td class="px-4 py-3 text-gray-600 max-w-[240px]">
                                            @forelse ($org->programs->take(2) as $prog)
                                                <div class="truncate text-xs" title="{{ $prog->name }}">
                                                    <span class="text-[10px] px-1 py-0.5 rounded bg-blue-50 text-[#2A57B4]">{{ $prog->college->code ?? '—' }}</span>
                                                    {{ $prog->name }}
                                                </div>
                                            @empty
                                                <span class="text-xs text-amber-600">Unassigned</span>
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
</div>