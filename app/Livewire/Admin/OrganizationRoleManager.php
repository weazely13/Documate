<?php

namespace App\Livewire\Admin;

use App\Models\College;
use App\Models\OfficerPosition;
use App\Models\Organization;
use App\Models\OrganizationOfficer;
use App\Models\Program;
use App\Models\Role;
use App\Models\SignatoryAssignment;
use App\Models\SignatoryRole;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\UserInvitation;
use App\Services\Workflow\SignatoryInvitations;
use Illuminate\Support\Facades\Auth;
use App\Services\Workflow\SignatoryScope;

class OrganizationRoleManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $panel = 'organizations'; // organizations | programs | positions | officers | signatories

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

    public string $filterSigSearch = '';
    public string $filterSigRole = '';
    public string $filterSigStatus = ''; // current | upcoming | expired | inactive

    // ---- Organizations form ----
    public ?int $editingOrgId = null;
    public string $orgName = '';
    public string $orgDescription = '';
    public array $orgProgramIds = [];
    public bool $orgIsAcademic = true;
    /** 1..4 for a year level organization, null/'' otherwise. Untyped on purpose: a <select> sends "". */
    public $orgYearLevel = null;

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

    // ---- Signatory roles form ----
    public ?int $editingSigRoleId = null;
    public string $sigRoleKey = '';
    public string $sigRoleLabel = '';
    public string $sigRoleDescription = '';

    // ---- Assign signatory form ----
    public string $sigUserSearch = '';
    public ?int $sigSelectedUserId = null;
    public ?int $sigRoleId = null;
    public ?string $sigActiveFrom = null;
    public ?string $sigActiveUntil = null;

    public string $invFirst = '';
    public string $invLast = '';
    public string $invEmail = '';
    public ?int $invRoleId = null;
    public ?string $invFrom = null;
    public ?string $invUntil = null;
    public string $sigRoleScope = 'none';
    public ?int $sigScopeId = null;
    public ?int $invScopeId = null;

    public function updatedInvRoleId(): void { $this->invScopeId = null; }

    // =========================================================
    // FORM HOOKS: year level vs academic
    // =========================================================

    /** Picking a year level makes the organization independent of programs. */
    public function updatedOrgYearLevel($value): void
    {
        if ($value !== null && $value !== '') {
            $this->orgIsAcademic = false;
            $this->orgProgramIds = [];
            $this->resetErrorBag('orgProgramIds');
        }
    }

    /** Ticking "Academic" again means it is not a year level organization. */
    public function updatedOrgIsAcademic($value): void
    {
        if ($value) {
            $this->orgYearLevel = null;
        }
    }

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
        } elseif (str_starts_with($name, 'filterSig')) {
            $this->resetPage('signatoryPage');
        }
    }

    public function clearFilters(string $which): void
    {
        match ($which) {
            'org' => $this->reset(['filterOrgSearch', 'filterOrgCollege', 'filterOrgProgram', 'filterOrgStatus']),
            'prog' => $this->reset(['filterProgSearch', 'filterProgCollege', 'filterProgStatus']),
            'off' => $this->reset(['filterOffSearch', 'filterOffCollege', 'filterOffOrg', 'filterOffPosition']),
            'sig' => $this->reset(['filterSigSearch', 'filterSigRole', 'filterSigStatus']),
            default => null,
        };
        $this->resetPage('orgPage');
        $this->resetPage('programPage');
        $this->resetPage('officerPage');
        $this->resetPage('signatoryPage');
    }

    // =========================================================
    // ORGANIZATIONS
    // =========================================================
    public function saveOrganization(): void
    {
        // Normalise the select value ("" -> null, "2" -> 2)
        $this->orgYearLevel = ($this->orgYearLevel === '' || $this->orgYearLevel === null)
            ? null
            : (int) $this->orgYearLevel;

        $isYearLevel = $this->orgYearLevel !== null;
        $isAcademic  = $isYearLevel ? false : $this->orgIsAcademic;

        $data = $this->validate([
            'orgName' => ['required', 'string', 'max:255', Rule::unique('organizations', 'name')->ignore($this->editingOrgId)],
            'orgDescription' => ['nullable', 'string', 'max:1000'],
            'orgIsAcademic' => ['boolean'],
            'orgProgramIds' => [Rule::requiredIf($isAcademic), 'array'],
            'orgProgramIds.*' => ['exists:programs,id'],
            'orgYearLevel' => [
                'nullable', 'integer', 'between:1,4',
                // only one organization per year level
                Rule::unique('organizations', 'year_level')->ignore($this->editingOrgId),
            ],
        ], [
            'orgYearLevel.unique' => 'There is already an organization for that year level.',
        ], [
            'orgName' => 'organization name',
            'orgProgramIds' => 'programs',
            'orgYearLevel' => 'year level',
        ]);

        // Year level organizations never have programs.
        $programIds = $isAcademic ? array_map('intval', $data['orgProgramIds'] ?? []) : [];

        if ($programIds !== []) {
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
        }

        // Don't drop a program that one of this organization's officers belongs to
        // (only relevant for organizations that actually use programs)
        if ($this->editingOrgId && $isAcademic) {
            $officerProgramIds = OrganizationOfficer::where('organization_officers.organization_id', $this->editingOrgId)
                ->join('users', 'users.id', '=', 'organization_officers.user_id')
                ->pluck('users.program_id')
                ->filter()
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
            [
                'name' => $data['orgName'],
                'description' => $data['orgDescription'] ?: null,
                'is_academic' => $isAcademic,
                'year_level' => $this->orgYearLevel,
            ],
        );
        $org->programs()->sync($programIds);

        session()->flash('org_message', $this->editingOrgId ? 'Organization updated.' : 'Organization created.');
        $this->resetOrganizationForm();
    }

    public function cancelInvitation(int $invitationId): void
    {
        try {
            app(SignatoryInvitations::class)->close($invitationId, 'cancelled');
        } catch (\RuntimeException $e) {
            session()->flash('sig_invite_message', 'That invitation was already used or closed.');
            return;
        }

        session()->flash('sig_invite_message', 'Invitation cancelled. The link no longer works.');
    }

    public function editOrganization(int $id): void
    {
        $org = Organization::with('programs')->findOrFail($id);
        $this->editingOrgId = $org->id;
        $this->orgName = $org->name;
        $this->orgDescription = (string) $org->description;
        $this->orgProgramIds = $org->programs->pluck('id')->map(fn ($i) => (string) $i)->all();
        $this->panel = 'organizations';
        $this->orgYearLevel = $org->year_level;
        $this->orgIsAcademic = $org->year_level !== null ? false : (bool) ($org->is_academic ?? true);
        $this->reset(['pickerSearch', 'pickerCollege', 'pickerView']);
    }

    public function resetOrganizationForm(): void
    {
        $this->reset(['editingOrgId', 'orgName', 'orgDescription', 'orgProgramIds', 'orgIsAcademic', 'orgYearLevel']);
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

    public function updatedSigRoleId(): void { $this->sigScopeId = null; }

    public function scopeOptions(string $scope): array { return SignatoryScope::options($scope); }

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
    // SIGNATORY ROLES (Guidance Counselor, VPSD, etc.)
    // =========================================================
    public function saveSignatoryRole(): void
    {
        $data = $this->validate([
            'sigRoleKey' => [
                'required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('signatory_roles', 'key')->ignore($this->editingSigRoleId),
            ],
            'sigRoleLabel' => ['required', 'string', 'max:255'],
            'sigRoleScope' => ['required', 'in:none,organization,program,college'],
            'sigRoleDescription' => ['nullable', 'string', 'max:1000'],
        ], [
            'sigRoleKey.regex' => 'Key may only contain lowercase letters, numbers, and underscores (e.g. guidance_counselor).',
        ], [
            'sigRoleKey' => 'role key',
            'sigRoleLabel' => 'role label',
        ]);

        if ($this->editingSigRoleId) {
            $existing = SignatoryRole::withCount('assignments')->find($this->editingSigRoleId);
            if ($existing && $existing->scope !== $data['sigRoleScope'] && $existing->assignments_count > 0) {
                $this->addError('sigRoleScope', 'This role already has assignments. Remove them before changing its scope.');
                return;
            }
        }

        SignatoryRole::updateOrCreate(
            ['id' => $this->editingSigRoleId],
            [
                'key' => $data['sigRoleKey'],
                'label' => $data['sigRoleLabel'],
                'description' => $data['sigRoleDescription'] ?: null,
                'scope' => $data['sigRoleScope'],
            ]
        );

        session()->flash('sig_role_message', $this->editingSigRoleId ? 'Signatory role updated.' : 'Signatory role added.');
        $this->resetSignatoryRoleForm();
    }

    public function editSignatoryRole(int $id): void
    {
        $role = SignatoryRole::findOrFail($id);
        $this->editingSigRoleId = $role->id;
        $this->sigRoleKey = $role->key;
        $this->sigRoleLabel = $role->label;
        $this->sigRoleDescription = (string) $role->description;
        $this->panel = 'signatories';
        $this->sigRoleScope = $role->scope;
    }

    public function resetSignatoryRoleForm(): void
    {
        $this->reset(['editingSigRoleId', 'sigRoleKey', 'sigRoleLabel', 'sigRoleDescription', 'sigRoleScope']);
        $this->resetErrorBag();
    }

    public function toggleSignatoryRoleActive(int $id): void
    {
        $role = SignatoryRole::findOrFail($id);

        // A role with transactions currently waiting on it cannot be switched off,
        // or every one of those approvals would become permanently unassignable.
        if ($role->is_active) {
            $openCount = DB::table('transaction_approvals')
                ->where('signatory_role_id', $role->id)
                ->where('status', 'pending')
                ->count();

            if ($openCount > 0) {
                session()->flash('sig_role_message', "Cannot deactivate \"{$role->label}\" — {$openCount} transaction(s) are currently waiting on this role.");
                return;
            }
        }

        $role->is_active = ! $role->is_active;
        $role->save();
    }

    public function deleteSignatoryRole(int $id): void
    {
        $role = SignatoryRole::withCount('assignments')->findOrFail($id);

        $usedInTemplates = DB::table('template_signatory_steps')->where('signatory_role_id', $id)->exists();

        if ($usedInTemplates) {
            session()->flash('sig_role_message', 'This role is used in a document template\'s approval chain. Remove it from the template first.');
            return;
        }

        if ($role->assignments_count > 0) {
            session()->flash('sig_role_message', 'People are still assigned to this role. Remove those assignments first.');
            return;
        }

        $role->delete();
        session()->flash('sig_role_message', 'Signatory role removed.');
    }

    // =========================================================
    // ASSIGN SIGNATORIES (who currently holds each role)
    // =========================================================
    public function updatingSigUserSearch(): void
    {
        $this->sigSelectedUserId = null;
    }

    public function selectSigUser(int $userId): void
    {
        $this->sigSelectedUserId = $userId;
        $this->sigUserSearch = '';
    }

    public function getSigSelectedUserProperty(): ?User
    {
        return $this->sigSelectedUserId ? User::find($this->sigSelectedUserId) : null;
    }

    public function getSigUserResultsProperty()
    {
        $search = trim($this->sigUserSearch);

        if (strlen($search) < 2) {
            return collect();
        }

        $term = '%' . $search . '%';

        return User::query()
            ->where(fn ($q) => $q
                ->where('student_number', 'like', $term)
                ->orWhere('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$term])
                ->orWhereRaw("CONCAT(last_name, ' ', first_name) LIKE ?", [$term])
                ->orWhere('email', 'like', $term))
            // exact / prefix student-number matches float to the top
            ->orderByRaw('CASE WHEN student_number LIKE ? THEN 0 ELSE 1 END', [$search . '%'])
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    public function assignSignatory(): void
    {
        $data = $this->validate([
            'sigSelectedUserId' => ['required', 'exists:users,id'],
            'sigRoleId' => ['required', 'exists:signatory_roles,id'],
            'sigActiveFrom' => ['nullable', 'date'],
            'sigActiveUntil' => ['nullable', 'date', 'after_or_equal:sigActiveFrom'],
        ], [], [
            'sigSelectedUserId' => 'person',
            'sigRoleId' => 'role',
            'sigActiveFrom' => 'start date',
            'sigActiveUntil' => 'end date',
        ]);
        $role = SignatoryRole::findOrFail($data['sigRoleId']);
        $scopeType = $role->scope !== 'none' ? $role->scope : null;
        $scopeId = $scopeType ? $this->sigScopeId : null;

        if ($scopeType && ! $scopeId) {
            $this->addError('sigScopeId', "Pick the {$scopeType} this person signs for.");
            return;
        }

        $alreadyCurrent = SignatoryAssignment::current()
            ->where('user_id', $data['sigSelectedUserId'])
            ->where('signatory_role_id', $data['sigRoleId'])
            ->where('scope_type', $scopeType)->where('scope_id', $scopeId)   // null matches null
            ->exists();

        if ($alreadyCurrent) {
            session()->flash('sig_assign_message', 'This person already currently holds that role.');
            return;
        }

        SignatoryAssignment::create([
            'user_id' => $data['sigSelectedUserId'],
            'signatory_role_id' => $data['sigRoleId'],
            'active_from' => $data['sigActiveFrom'] ?: null,
            'active_until' => $data['sigActiveUntil'] ?: null,
            'is_active' => true,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
        ]);

        $user = User::find($data['sigSelectedUserId']);
        $role = SignatoryRole::find($data['sigRoleId']);

        session()->flash('sig_assign_message', trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) . " is now assigned as {$role->label}.");

        $this->reset(['sigSelectedUserId', 'sigRoleId', 'sigActiveFrom', 'sigActiveUntil', 'sigUserSearch']);
        $this->resetPage('signatoryPage');
    }

    public function endSignatoryAssignment(int $assignmentId): void
    {
        $assignment = SignatoryAssignment::with('role')->findOrFail($assignmentId);

        // If this is the only current holder of the role and something is actively
        // waiting on that role, block it — otherwise the approval becomes unassignable.
        $remainingHolders = $assignment->role
            ->holdersFor($assignment->scope_type, $assignment->scope_id)
            ->where('users.id', '<>', $assignment->user_id)->count();

        if ($remainingHolders === 0) {
            $openCount = DB::table('transaction_approvals')
                ->where('signatory_role_id', $assignment->signatory_role_id)
                ->where('status', 'pending')
                ->when($assignment->scope_type, fn ($q) => $q->where('scope_type', $assignment->scope_type)->where('scope_id', $assignment->scope_id))
                ->count();

            if ($openCount > 0) {
                session()->flash('sig_assign_message', "Cannot remove the last holder of \"{$assignment->role->label}\" — {$openCount} transaction(s) are currently waiting on this role. Assign a replacement first.");
                return;
            }
        }

        $assignment->update(['is_active' => false, 'active_until' => $assignment->active_until ?? today()]);

        session()->flash('sig_assign_message', 'Signatory assignment ended.');
    }

    public function reactivateSignatoryAssignment(int $assignmentId): void
    {
        $assignment = SignatoryAssignment::findOrFail($assignmentId);
        $assignment->update(['is_active' => true]);

        session()->flash('sig_assign_message', 'Signatory assignment reactivated.');
    }

    public function deleteSignatoryAssignment(int $assignmentId): void
    {
        SignatoryAssignment::findOrFail($assignmentId)->delete();
        session()->flash('sig_assign_message', 'Signatory assignment deleted.');
    }

    public function inviteSignatory(): void
    {
        $data = $this->validate([
            'invFirst' => ['required', 'string', 'max:100'],
            'invLast' => ['required', 'string', 'max:100'],
            'invEmail' => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
            'invRoleId' => ['required', 'exists:signatory_roles,id'],
            'invFrom' => ['nullable', 'date'],
            'invUntil' => ['nullable', 'date', 'after_or_equal:invFrom'],
        ], [
            'invEmail.unique' => 'That email already has an account. Use "Assign a Signatory" above instead.',
        ], [
            'invFirst' => 'first name', 'invLast' => 'last name', 'invEmail' => 'email',
            'invRoleId' => 'signatory role', 'invFrom' => 'start date', 'invUntil' => 'end date',
        ]);
        $role = SignatoryRole::findOrFail($data['invRoleId']);
        $scopeType = $role->scope !== 'none' ? $role->scope : null;

        if ($scopeType && ! $this->invScopeId) {
            $this->addError('invScopeId', "Pick the {$scopeType} this person signs for.");
            return;
        }

        try {
            app(SignatoryInvitations::class)->invite([
                'first_name' => trim($data['invFirst']),
                'last_name' => trim($data['invLast']),
                'email' => $data['invEmail'],
                'signatory_role_id' => $data['invRoleId'],
                'active_from' => $data['invFrom'] ?? null,
                'active_until' => $data['invUntil'] ?? null,
                'scope_type' => $scopeType,
                'scope_id' => $scopeType ? $this->invScopeId : null,
            ], Auth::user());
        } catch (\Throwable $e) {
            report($e);
            session()->flash('sig_invite_message', 'The invitation could not be sent, so no account was created. Check the mail settings and try again.');

            return;
        }

        session()->flash('sig_invite_message', trim($data['invFirst'] . ' ' . $data['invLast']) . ' was invited. They will receive an email to set their password.');
        $this->reset(['invFirst', 'invLast', 'invEmail', 'invRoleId', 'invFrom', 'invUntil', 'sigScopeId', 'invScopeId']);
    }

    /** New setup link. Also serves as "reset password" for a signatory who already has one. */
    public function resendInvitation(int $userId): void
    {
        $user = User::whereHas('role', fn ($q) => $q->where('role_name', 'Signatory'))->findOrFail($userId);

        try {
            app(SignatoryInvitations::class)->resend($user, Auth::user());
        } catch (\Throwable $e) {
            report($e);
            session()->flash('sig_invite_message', 'The email could not be sent. Check the mail settings and try again.');

            return;
        }

        session()->flash('sig_invite_message', 'A new setup link was emailed to ' . $user->email . '. Older links no longer work.');
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
                'signatories' => SignatoryAssignment::where('is_active', true)->count(),
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

        if ($this->panel === 'signatories') {

            $data['invitations'] = UserInvitation::with('user')
                ->whereHas('user.role', fn ($q) => $q->where('role_name', 'Signatory'))
                ->latest('id')->limit(100)->get()
                ->unique('user_id')->values();
            $data['signatoryRoles'] = SignatoryRole::withCount('assignments')->orderBy('label')->get();

            // How many active, currently-in-window holders each role has — used to
            // warn in the UI before a role ends up with zero holders.
            $data['sigHolderCounts'] = SignatoryAssignment::current()
                ->selectRaw('signatory_role_id, count(*) as c')
                ->groupBy('signatory_role_id')
                ->pluck('c', 'signatory_role_id');

            $today = today()->toDateString();

            $data['signatoryAssignments'] = SignatoryAssignment::with(['user', 'role'])
                ->when($this->filterSigSearch, function ($q, $s) {
                    $q->whereHas('user', fn ($u) => $u->where(fn ($w) => $w
                        ->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                        ->orWhere('email', 'like', "%{$s}%")
                        ->orWhere('student_number', 'like', "%{$s}%")));
                })
                ->when($this->filterSigRole, fn ($q, $r) => $q->where('signatory_role_id', $r))
                ->when($this->filterSigStatus === 'current', fn ($q) => $q->current())
                ->when($this->filterSigStatus === 'inactive', fn ($q) => $q->where('is_active', false))
                ->when($this->filterSigStatus === 'upcoming', fn ($q) => $q->where('is_active', true)->where('active_from', '>', $today))
                ->when($this->filterSigStatus === 'expired', fn ($q) => $q->where('is_active', true)->whereNotNull('active_until')->where('active_until', '<', $today))
                ->orderByDesc('is_active')
                ->orderBy('active_from')
                ->paginate(15, pageName: 'signatoryPage');

            $data['sigSelectedUser'] = $this->sigSelectedUser;
        }

        return view('livewire.admin.organization-role-manager', $data);
    }
}