<div class="space-y-6">

    {{-- Sub-nav --}}
    <div class="flex flex-wrap gap-2 border-b border-gray-200 pb-3">
        @foreach ([
            'organizations' => 'Organizations',
            'positions' => 'Officer Positions',
            'programs' => 'Programs',
            'officers' => 'Assign Officers',
        ] as $key => $label)
            <button type="button" wire:click="$set('panel', '{{ $key }}')"
                class="px-4 py-2 text-sm font-medium rounded-xl transition
                    {{ $panel === $key ? 'bg-[#2A57B4] text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ============ ORGANIZATIONS ============ --}}
    @if ($panel === 'organizations')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm h-fit">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $editingOrgId ? 'Edit Organization' : 'Add Organization' }}
                </h3>

                @if (session('org_message'))
                    <div class="mb-3 text-sm rounded-lg bg-blue-50 text-blue-700 px-3 py-2">{{ session('org_message') }}</div>
                @endif

                <form wire:submit="saveOrganization" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Name</label>
                        <input type="text" wire:model="orgName" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                        @error('orgName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Description</label>
                        <textarea wire:model="orgDescription" rows="3" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]"></textarea>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            {{ $editingOrgId ? 'Save Changes' : 'Add Organization' }}
                        </button>
                        @if ($editingOrgId)
                            <button type="button" wire:click="resetOrganizationForm" class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-sm table-fixed">
                    <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
                        <tr>
                            <th class="px-4 py-3 text-left w-2/5">Name</th>
                            <th class="px-4 py-3 text-left w-1/6">Members</th>
                            <th class="px-4 py-3 text-left w-1/6">Officers</th>
                            <th class="px-4 py-3 text-left w-1/6">Status</th>
                            <th class="px-4 py-3 text-right w-1/6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($organizations as $org)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <div class="truncate" title="{{ $org->name }}">{{ $org->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $org->members_count }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $org->officers_count }}</td>
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
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-400">No organizations yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============ OFFICER POSITIONS ============ --}}
    @if ($panel === 'positions')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm h-fit">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $editingPositionId ? 'Edit Position' : 'Add Officer Position' }}
                </h3>

                @if (session('position_message'))
                    <div class="mb-3 text-sm rounded-lg bg-blue-50 text-blue-700 px-3 py-2">{{ session('position_message') }}</div>
                @endif

                <form wire:submit="savePosition" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Title</label>
                        <input type="text" wire:model="positionTitle" placeholder="e.g. President, Auditor" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                        @error('positionTitle') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            {{ $editingPositionId ? 'Save Changes' : 'Add Position' }}
                        </button>
                        @if ($editingPositionId)
                            <button type="button" wire:click="resetPositionForm" class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-sm table-fixed">
                    <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
                        <tr>
                            <th class="px-4 py-3 text-left w-4/5">Title</th>
                            <th class="px-4 py-3 text-right w-1/5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($positions as $position)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <div class="truncate" title="{{ $position->title }}">{{ $position->title }}</div>
                                </td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    <button wire:click="editPosition({{ $position->id }})" class="text-xs font-medium text-blue-600 hover:underline">Edit</button>
                                    <button wire:click="deletePosition({{ $position->id }})" wire:confirm="Remove this position?" class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-4 py-6 text-center text-gray-400">No positions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============ PROGRAMS ============ --}}
    @if ($panel === 'programs')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm h-fit">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">
                    {{ $editingProgramId ? 'Edit Program' : 'Add Program' }}
                </h3>

                @if (session('program_message'))
                    <div class="mb-3 text-sm rounded-lg bg-blue-50 text-blue-700 px-3 py-2">{{ session('program_message') }}</div>
                @endif

                <form wire:submit="saveProgram" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Program Name</label>
                        <input type="text" wire:model="programName" placeholder="e.g. Bachelor of Science in Nursing" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                        @error('programName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Level</label>
                        <select wire:model="programLevel" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                            <option value="bachelor">Bachelor's</option>
                            <option value="master">Master's</option>
                        </select>
                    </div>
                    <div class="flex gap-2 pt-1">
                        <button type="submit" class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            {{ $editingProgramId ? 'Save Changes' : 'Add Program' }}
                        </button>
                        @if ($editingProgramId)
                            <button type="button" wire:click="resetProgramForm" class="rounded-xl border border-gray-200 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                Cancel
                            </button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-sm table-fixed">
                    <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
                        <tr>
                            <th class="px-4 py-3 text-left w-2/5">Program</th>
                            <th class="px-4 py-3 text-left w-1/5">Level</th>
                            <th class="px-4 py-3 text-left w-1/5">Status</th>
                            <th class="px-4 py-3 text-right w-1/5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($programs as $program)
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <div class="truncate" title="{{ $program->name }}">{{ $program->name }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <div class="truncate" title="{{ $program->level_label }}">{{ $program->level_label }}</div>
                                </td>
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
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No programs yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ============ ASSIGN OFFICERS ============ --}}
    @if ($panel === 'officers')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white border border-gray-200 rounded-2xl p-5 shadow-sm h-fit">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Make a Student an Officer</h3>

                @if (session('assign_message'))
                    <div class="mb-3 text-sm rounded-lg bg-blue-50 text-blue-700 px-3 py-2">{{ session('assign_message') }}</div>
                @endif

                <div class="space-y-3">
                    <div class="relative">
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Find Student</label>

                        @if ($selectedStudent)
                            <div class="mt-1 flex items-center justify-between gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm">
                                <span class="truncate" title="{{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }} ({{ $selectedStudent->student_number }})">
                                    {{ $selectedStudent->first_name }} {{ $selectedStudent->last_name }} ({{ $selectedStudent->student_number }})
                                </span>
                                <button type="button" wire:click="$set('selectedStudentId', null)" class="shrink-0 text-gray-400 hover:text-gray-700">✕</button>
                            </div>
                        @else
                            <input type="text" wire:model.live.debounce.300ms="studentSearch"
                                placeholder="Search by name or student number"
                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">

                            @if ($this->studentResults->isNotEmpty())
                                <div class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow">
                                    @foreach ($this->studentResults as $student)
                                        <div wire:click="selectStudent({{ $student->id }})"
                                            class="cursor-pointer px-3 py-2 text-sm hover:bg-gray-50 flex items-center gap-1"
                                            title="{{ $student->first_name }} {{ $student->last_name }} ({{ $student->student_number }})">
                                            <span class="truncate">{{ $student->first_name }} {{ $student->last_name }}</span>
                                            <span class="text-xs text-gray-400 shrink-0">({{ $student->student_number }})</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                        @error('selectedStudentId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Organization</label>
                        <select wire:model="assignOrganizationId" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                            <option value="">Select…</option>
                            @foreach ($organizations->where('is_active', true) as $org)
                                <option value="{{ $org->id }}">{{ \Illuminate\Support\Str::limit($org->name, 40) }}</option>
                            @endforeach
                        </select>
                        @error('assignOrganizationId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase">Officer Position</label>
                        <select wire:model="assignPositionId" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]">
                            <option value="">Select…</option>
                            @foreach ($positions as $position)
                                <option value="{{ $position->id }}">{{ \Illuminate\Support\Str::limit($position->title, 40) }}</option>
                            @endforeach
                        </select>
                        @error('assignPositionId') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <button type="button" wire:click="assignOfficer" class="w-full rounded-xl bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        Assign Officer
                    </button>
                    <p class="text-xs text-gray-400">Assigning locks the student's organization on their profile until the role is removed.</p>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <table class="w-full text-sm table-fixed">
                    <thead class="bg-[#2A57B4]/5 text-xs uppercase text-[#2A57B4]">
                        <tr>
                            <th class="px-4 py-3 text-left w-1/3">Student</th>
                            <th class="px-4 py-3 text-left w-1/4">Organization</th>
                            <th class="px-4 py-3 text-left w-1/4">Position</th>
                            <th class="px-4 py-3 text-right w-1/6">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($officers as $officer)
                            @php
                                $studentName = trim(($officer->user->first_name ?? '') . ' ' . ($officer->user->last_name ?? ''));
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <div class="truncate" title="{{ $studentName }}">{{ $studentName }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <div class="truncate" title="{{ $officer->organization->name ?? '—' }}">{{ $officer->organization->name ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <div class="truncate" title="{{ $officer->position->title ?? '—' }}">{{ $officer->position->title ?? '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button wire:click="removeOfficer({{ $officer->id }})" wire:confirm="Remove this officer role?" class="text-xs font-medium text-red-600 hover:underline">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No officers assigned yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>