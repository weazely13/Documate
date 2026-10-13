<?php

namespace App\Livewire\Admin;

use App\Models\College;
use App\Models\OfficerPosition;
use App\Models\Organization;
use App\Models\OrganizationOfficer;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class OrganizationRoleManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $panel = 'organizations'; // organizations | programs | positions | officers

    // ---- Filters (prefix decides which paginator gets reset) ----
    public string $filterOrgSearch = '';
    public string $filterOrgCollege = '';
    public string $filterOrgProgram = '';
    public string $filterOrgStatus = '';

    public string $filterProgSearch = '';
    public string $filterProgCollege = '';
    public string $filterProgStatus = '';

    public string $filterOffSearch = '';
    public string $filterOffCollege = '';
    public string $filterOffOrg = '';
    public string $filterOffPosition = '';

    // ---- Organizations form ----
    public ?int $editingOrgId = null;
    public string $orgName = '';
    public string $orgDescription = '';
    public array $orgProgramIds = [];

    // ---- Positions form ----
    public ?int $editingPositionId = null;
    public string $positionTitle = '';

    // ---- Programs form ----
    public ?int $editingProgramId = null;
    public string $programName = '';
    public ?int $programCollegeId = null;

    // ---- Assign officer ----
    public string $studentSearch = '';
    public ?int $selectedStudentId = null;
    public ?int $assignOrganizationId = null;
    public ?int $assignPositionId = null;

    public string $pickerSearch = '';
    public string $pickerCollege = '';
    public string $pickerView = 'all';

    // =========================================================
    // FILTER / PAGINATION HOOKS
    // =========================================================
    public function updated($name): void
    {
        if (str_starts_with($name, 'filterOrg')) {
            if ($name === 'filterOrgCollege') {
                $this->filterOrgProgram = '';
            }
            $this->resetPage('orgPage');
        } elseif (str_starts_with($name, 'filterProg')) {
            $this->resetPage('programPage');
        } elseif (str_starts_with($name, 'filterOff')) {
            $this->resetPage('officerPage');
        }
    }

    public function clearFilters(string $which): void
    {
        match ($which) {
            'org' => $this->reset(['filterOrgSearch', 'filterOrgCollege', 'filterOrgProgram', 'filterOrgStatus']),
            'prog' => $this->reset(['filterProgSearch', 'filterProgCollege', 'filterProgStatus']),
            'off' => $this->reset(['filterOffSearch', 'filterOffCollege', 'filterOffOrg', 'filterOffPosition']),
            default => null,
        };
        $this->resetPage('orgPage');
        $this->resetPage('programPage');
        $this->resetPage('officerPage');
    }

    // =========================================================
    // ORGANIZATIONS
    // =========================================================
    public function saveOrganization(): void
    {
        $data = $this->validate([
            'orgName' => ['required', 'string', 'max:255', Rule::unique('organizations', 'name')->ignore($this->editingOrgId)],
            'orgDescription' => ['nullable', 'string', 'max:1000'],
            'orgProgramIds' => ['required', 'array', 'min:1'],
            'orgProgramIds.*' => ['exists:programs,id'],
        ], [], [
            'orgName' => 'organization name',
            'orgProgramIds' => 'programs',
        ]);

        $programIds = array_map('intval', $data['orgProgramIds']);
        $taken = $this->takenProgramMap()->keys()->intersect($programIds);
        // A program can belong to only one organization
        $taken = DB::table('organization_program')
            ->whereIn('program_id', $programIds)
            ->when($this->editingOrgId, fn ($q) => $q->where('organization_id', '<>', $this->editingOrgId))
            ->pluck('program_id');

        if ($taken->isNotEmpty()) {
            $names = Program::whereIn('id', $taken)->pluck('name')->implode(', ');
            $this->addError('orgProgramIds', "Already assigned to another organization: {$names}.");
            return;
        }

        // Don't drop a program that one of this organization's officers belongs to
        if ($this->editingOrgId) {
            $officerProgramIds = OrganizationOfficer::where('organization_officers.organization_id', $this->editingOrgId)
                ->join('users', 'users.id', '=', 'organization_officers.user_id')
                ->pluck('users.program_id')
                ->filter()          // ignore officers whose program is null
                ->unique()
                ->map(fn ($id) => (int) $id)
                ->all();

            if (array_diff($officerProgramIds, $programIds)) {
                session()->flash('org_message', 'An officer belongs to a program you unticked. Remove that officer first, or keep the program.');
                return;
            }
        }

        $org = Organization::updateOrCreate(
            ['id' => $this->editingOrgId],
            ['name' => $data['orgName'], 'description' => $data['orgDescription'] ?: null]
        );
        $org->programs()->sync($programIds);

        session()->flash('org_message', $this->editingOrgId ? 'Organization updated.' : 'Organization created.');
        $this->reset(['editingOrgId', 'orgName', 'orgDescription', 'orgProgramIds', 'pickerSearch', 'pickerCollege', 'pickerView']);
        $this->resetOrganizationForm();
    }

    public function editOrganization(int $id): void
    {
        $org = Organization::with('programs')->findOrFail($id);
        $this->editingOrgId = $org->id;
        $this->orgName = $org->name;
        $this->orgDescription = (string) $org->description;
        $this->orgProgramIds = $org->programs->pluck('id')->map(fn ($i) => (string) $i)->all();
        $this->panel = 'organizations';
        $this->reset(['pickerSearch', 'pickerCollege', 'pickerView']);
    }

    public function resetOrganizationForm(): void
    {
        $this->reset(['editingOrgId', 'orgName', 'orgDescription', 'orgProgramIds']);
        $this->resetErrorBag();
    }

    public function toggleOrganizationActive(int $id): void
    {
        $org = Organization::findOrFail($id);
        $org->is_active = ! $org->is_active;
        $org->save();
    }

    public function deleteOrganization(int $id): void
    {
        $org = Organization::withCount('officers', 'members')->findOrFail($id);

        if ($org->officers_count > 0 || $org->members_count > 0) {
            session()->flash('org_message', 'This organization still has members or officers assigned. Reassign or remove them first.');
            return;
        }

        $org->delete();
        session()->flash('org_message', 'Organization removed.');
    }

    protected function takenProgramMap()
    {
        return DB::table('organization_program')
            ->join('organizations', 'organizations.id', '=', 'organization_program.organization_id')
            ->when($this->editingOrgId, fn ($q) => $q->where('organization_program.organization_id', '<>', $this->editingOrgId))
            ->pluck('organizations.name', 'organization_program.program_id');
    }

    public function getSelectedProgramsProperty()
    {
        $ids = array_map('intval', $this->orgProgramIds);

        return $ids
            ? Program::with('college')->whereIn('id', $ids)->orderBy('name')->get()
            : collect();
    }

    public function getPickerProgramsProperty()
    {
        $selected = array_map('intval', $this->orgProgramIds);
        $taken = $this->takenProgramMap()->keys()->all();

        return Program::with('college')
            // active programs, plus any inactive ones already linked (so they can be unchecked)
            ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $selected))
            ->when($this->pickerSearch, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($this->pickerCollege, fn ($q, $c) => $q->where('college_id', $c))
            ->when($this->pickerView === 'selected', fn ($q) => $q->whereIn('id', $selected))
            ->when($this->pickerView === 'available', fn ($q) => $q->whereNotIn('id', $taken)->whereNotIn('id', $selected))
            ->orderBy('name')
            ->get();
    }

    public function removeProgram(int $id): void
    {
        $this->orgProgramIds = array_values(array_filter(
            $this->orgProgramIds,
            fn ($v) => (int) $v !== $id
        ));
    }

    public function selectVisiblePrograms(): void
    {
        $taken = $this->takenProgramMap();

        $ids = $this->pickerPrograms
            ->filter(fn ($p) => $p->is_active && ! $taken->has($p->id))
            ->pluck('id')
            ->map(fn ($i) => (string) $i)
            ->all();

        $this->orgProgramIds = array_values(array_unique(array_merge($this->orgProgramIds, $ids)));
    }

    public function clearPrograms(): void
    {
        $this->orgProgramIds = [];
    }

    // =========================================================
    // OFFICER POSITIONS
    // =========================================================
    public function savePosition(): void
    {
        $data = $this->validate([
            'positionTitle' => ['required', 'string', 'max:255', Rule::unique('officer_positions', 'title')->ignore($this->editingPositionId)],
        ], [], ['positionTitle' => 'position title']);

        OfficerPosition::updateOrCreate(
            ['id' => $this->editingPositionId],
            [
                'title' => $data['positionTitle'],
                'sort_order' => $this->editingPositionId
                    ? OfficerPosition::find($this->editingPositionId)->sort_order
                    : (OfficerPosition::max('sort_order') + 1),
            ]
        );

        session()->flash('position_message', $this->editingPositionId ? 'Position updated.' : 'Position added.');
        $this->resetPositionForm();
    }

    public function editPosition(int $id): void
    {
        $position = OfficerPosition::findOrFail($id);
        $this->editingPositionId = $position->id;
        $this->positionTitle = $position->title;
        $this->panel = 'positions';
    }

    public function deletePosition(int $id): void
    {
        $position = OfficerPosition::withCount('officers')->findOrFail($id);

        if ($position->officers_count > 0) {
            session()->flash('position_message', 'This position is currently held by an officer. Reassign them first.');
            return;
        }

        $position->delete();
        session()->flash('position_message', 'Position removed.');
    }

    public function resetPositionForm(): void
    {
        $this->reset(['editingPositionId', 'positionTitle']);
        $this->resetErrorBag();
    }

    // =========================================================
    // PROGRAMS
    // =========================================================
    public function saveProgram(): void
    {
        $data = $this->validate([
            'programName' => ['required', 'string', 'max:255', Rule::unique('programs', 'name')->ignore($this->editingProgramId)],
            'programCollegeId' => ['required', 'exists:colleges,id'],
        ], [], ['programName' => 'program name', 'programCollegeId' => 'college']);

        Program::updateOrCreate(
            ['id' => $this->editingProgramId],
            ['name' => $data['programName'], 'college_id' => $data['programCollegeId']]
        );

        session()->flash('program_message', $this->editingProgramId ? 'Program updated.' : 'Program added.');
        $this->resetProgramForm();
    }

    public function editProgram(int $id): void
    {
        $program = Program::findOrFail($id);
        $this->editingProgramId = $program->id;
        $this->programName = $program->name;
        $this->programCollegeId = $program->college_id;
        $this->panel = 'programs';
    }

    public function toggleProgramActive(int $id): void
    {
        $program = Program::findOrFail($id);
        $program->is_active = ! $program->is_active;
        $program->save();
    }

    public function deleteProgram(int $id): void
    {
        $program = Program::withCount('students', 'organizations')->findOrFail($id);

        if ($program->students_count > 0 || $program->organizations_count > 0) {
            session()->flash('program_message', 'Students or organizations still belong to this program. Reassign them first.');
            return;
        }

        $program->delete();
        session()->flash('program_message', 'Program removed.');
    }

    public function resetProgramForm(): void
    {
        $this->reset(['editingProgramId', 'programName', 'programCollegeId']);
        $this->resetErrorBag();
    }

    // =========================================================
    // ASSIGN OFFICER (restricted to the organization's program)
    // =========================================================
    public function updatedAssignOrganizationId(): void
    {
        // Eligible students depend on the organization's program, so start over.
        $this->reset(['selectedStudentId', 'studentSearch']);
    }

    public function updatingStudentSearch(): void
    {
        $this->selectedStudentId = null;
    }

    public function selectStudent(int $userId): void
    {
        $this->selectedStudentId = $userId;
        $this->studentSearch = '';
    }

    public function getAssignOrganizationProperty(): ?Organization
    {
        return $this->assignOrganizationId
            ? Organization::with('programs')->find($this->assignOrganizationId)
            : null;
    }

    public function getStudentResultsProperty()
    {
        $programIds = $this->assignOrganization?->programs->pluck('id');

        if (! $programIds || $programIds->isEmpty() || strlen($this->studentSearch) < 2) {
            return collect();
        }

        $term = '%' . $this->studentSearch . '%';

        return User::query()
            ->whereIn('program_id', $programIds)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Student'))
            ->whereDoesntHave('officerRecord')
            ->where(fn ($q) => $q
                ->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('student_number', 'like', $term))
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    public function getEligibleCountProperty(): int
    {
        $programIds = $this->assignOrganization?->programs->pluck('id');

        if (! $programIds || $programIds->isEmpty()) {
            return 0;
        }

        return User::whereIn('program_id', $programIds)
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Student'))
            ->whereDoesntHave('officerRecord')
            ->count();
    }

    public function assignOfficer(): void
    {
        $this->validate([
            'selectedStudentId' => ['required', 'exists:users,id'],
            'assignOrganizationId' => ['required', 'exists:organizations,id'],
            'assignPositionId' => ['required', 'exists:officer_positions,id'],
        ], [], [
            'selectedStudentId' => 'student',
            'assignOrganizationId' => 'organization',
            'assignPositionId' => 'position',
        ]);

        $org = Organization::with('programs')->findOrFail($this->assignOrganizationId);
        $user = User::with('role')->findOrFail($this->selectedStudentId);

        if ($org->programs->isEmpty()) {
            session()->flash('assign_message', 'This organization has no programs yet. Assign at least one first.');
            return;
        }

        if (Str::lower((string) ($user->role->role_name ?? '')) === 'admin') {
            session()->flash('assign_message', 'The administrator account cannot be assigned as an officer.');
            return;
        }

        if (! $org->programs->pluck('id')->contains($user->program_id)) {
            session()->flash('assign_message', 'Only students from this organization\'s programs can be assigned as its officers.');
            return;
        }

        if ($user->isOfficer()) {
            session()->flash('assign_message', 'This student is already an officer. Remove their current role first.');
            return;
        }

        $officerRole = Role::where('role_name', 'Officer')->first();

        $user->organization_id = $org->id;
        if ($officerRole) {
            $user->role_id = $officerRole->id;
        }
        $user->save();

        OrganizationOfficer::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'officer_position_id' => $this->assignPositionId,
        ]);

        session()->flash('assign_message', trim($user->first_name . ' ' . $user->last_name) . ' is now an officer of ' . $org->name . '.');

        $this->reset(['selectedStudentId', 'assignPositionId', 'studentSearch']);
        $this->resetPage('officerPage');
    }

    public function removeOfficer(int $officerRecordId): void
    {
        $record = OrganizationOfficer::with('user')->findOrFail($officerRecordId);

        $studentRole = Role::where('role_name', 'Student')->first();
        if ($record->user && $studentRole) {
            $record->user->role_id = $studentRole->id;
            $record->user->save();
        }

        $record->delete();

        session()->flash('assign_message', 'Officer role removed. The student can change their organization again.');
    }

    // =========================================================
    // RENDER
    // =========================================================
    public function render()
    {
        $data = [
            'colleges' => College::with(['programs' => fn ($q) => $q->active()->orderBy('name')])->orderBy('code')->get(),
            'counts' => [
                'organizations' => Organization::count(),
                'programs' => Program::count(),
                'positions' => OfficerPosition::count(),
                'officers' => OrganizationOfficer::count(),
            ],
        ];

        if ($this->panel === 'organizations') {
            $data['filterPrograms'] = Program::when($this->filterOrgCollege, fn ($q, $c) => $q->where('college_id', $c))
                ->orderBy('name')->get();

            $data['organizations'] = Organization::with('programs.college')
                ->withCount('members', 'officers')
                ->when($this->filterOrgSearch, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($this->filterOrgCollege, fn ($q, $c) => $q->whereHas('programs', fn ($p) => $p->where('college_id', $c)))
                ->when($this->filterOrgProgram, fn ($q, $p) => $q->forProgram($p))
                ->when($this->filterOrgStatus !== '', fn ($q) => $q->where('is_active', $this->filterOrgStatus === '1'))
                ->orderBy('name')
                ->paginate(10, pageName: 'orgPage');
            $data['takenPrograms'] = $this->takenProgramMap();
        }

        if ($this->panel === 'programs') {
            $data['programs'] = Program::with('college')
                ->withCount('students', 'organizations')
                ->when($this->filterProgSearch, fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
                ->when($this->filterProgCollege, fn ($q, $c) => $q->where('college_id', $c))
                ->when($this->filterProgStatus !== '', fn ($q) => $q->where('is_active', $this->filterProgStatus === '1'))
                ->orderBy('name')
                ->paginate(10, pageName: 'programPage');
        }

        if ($this->panel === 'positions') {
            $data['positions'] = OfficerPosition::withCount('officers')->orderBy('sort_order')->orderBy('title')->get();
        }

        if ($this->panel === 'officers') {
            $data['positions'] = OfficerPosition::orderBy('sort_order')->orderBy('title')->get();

            $data['orgOptions'] = Organization::active()->has('programs')->with('programs')->orderBy('name')->get();

            $data['filterOrgs'] = Organization::orderBy('name')->get(['id', 'name']);

            $data['officers'] = OrganizationOfficer::with(['user', 'organization.programs', 'position'])
                ->when($this->filterOffSearch, function ($q, $s) {
                    $q->whereHas('user', fn ($u) => $u->where(fn ($w) => $w
                        ->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                        ->orWhere('student_number', 'like', "%{$s}%")));
                })
                ->when($this->filterOffCollege, fn ($q, $c) => $q->whereHas('organization.programs', fn ($p) => $p->where('college_id', $c)))
                ->when($this->filterOffOrg, fn ($q, $o) => $q->where('organization_id', $o))
                ->when($this->filterOffPosition, fn ($q, $p) => $q->where('officer_position_id', $p))
                ->latest()
                ->paginate(15, pageName: 'officerPage');

            $data['selectedStudent'] = $this->selectedStudentId ? User::with('program')->find($this->selectedStudentId) : null;
        }

        return view('livewire.admin.organization-role-manager', $data);
    }
}