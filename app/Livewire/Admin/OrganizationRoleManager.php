<?php

namespace App\Livewire\Admin;

use App\Models\Organization;
use App\Models\OfficerPosition;
use App\Models\OrganizationOfficer;
use App\Models\Program;
use App\Models\Role;  
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class OrganizationRoleManager extends Component
{
    // Which sub-panel is showing inside this tab
    public string $panel = 'organizations'; // organizations | positions | programs | officers

    // ---- Organizations ----
    public ?int $editingOrgId = null;
    public string $orgName = '';
    public string $orgDescription = '';

    // ---- Officer positions ----
    public ?int $editingPositionId = null;
    public string $positionTitle = '';

    // ---- Programs ----
    public ?int $editingProgramId = null;
    public string $programName = '';
    public string $programLevel = 'bachelor';

    // ---- Assign officer ----
    public string $studentSearch = '';
    public ?int $selectedStudentId = null;
    public ?int $assignOrganizationId = null;
    public ?int $assignPositionId = null;

    protected $paginationTheme = 'tailwind';

    public function updatingStudentSearch()
    {
        $this->selectedStudentId = null;
    }

    // =========================================================
    // ORGANIZATIONS
    // =========================================================
    public function saveOrganization(): void
    {
        $data = $this->validate([
            'orgName' => ['required', 'string', 'max:255', Rule::unique('organizations', 'name')->ignore($this->editingOrgId)],
            'orgDescription' => ['nullable', 'string', 'max:1000'],
        ], [], ['orgName' => 'organization name']);

        Organization::updateOrCreate(
            ['id' => $this->editingOrgId],
            ['name' => $data['orgName'], 'description' => $data['orgDescription'] ?: null]
        );

        session()->flash('org_message', $this->editingOrgId ? 'Organization updated.' : 'Organization created.');
        $this->resetOrganizationForm();
    }

    public function editOrganization(int $id): void
    {
        $org = Organization::findOrFail($id);
        $this->editingOrgId = $org->id;
        $this->orgName = $org->name;
        $this->orgDescription = (string) $org->description;
        $this->panel = 'organizations';
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

    public function resetOrganizationForm(): void
    {
        $this->reset(['editingOrgId', 'orgName', 'orgDescription']);
        $this->resetErrorBag();
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
            'programLevel' => ['required', Rule::in(['bachelor', 'master'])],
        ], [], ['programName' => 'program name']);

        Program::updateOrCreate(
            ['id' => $this->editingProgramId],
            ['name' => $data['programName'], 'level' => $data['programLevel']]
        );

        session()->flash('program_message', $this->editingProgramId ? 'Program updated.' : 'Program added.');
        $this->resetProgramForm();
    }

    public function editProgram(int $id): void
    {
        $program = Program::findOrFail($id);
        $this->editingProgramId = $program->id;
        $this->programName = $program->name;
        $this->programLevel = $program->level;
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
        $program = Program::withCount('students')->findOrFail($id);

        if ($program->students_count > 0) {
            session()->flash('program_message', 'Students are currently enrolled under this program. Reassign them first.');
            return;
        }

        $program->delete();
        session()->flash('program_message', 'Program removed.');
    }

    public function resetProgramForm(): void
    {
        $this->reset(['editingProgramId', 'programName']);
        $this->programLevel = 'bachelor';
        $this->resetErrorBag();
    }

    // =========================================================
    // ASSIGN OFFICER
    // =========================================================
    public function selectStudent(int $userId): void
    {
        $this->selectedStudentId = $userId;
        $this->studentSearch = '';
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

        $user = User::with('role')->findOrFail($this->selectedStudentId);

        if (Str::lower((string) ($user->role->role_name ?? '')) === 'admin') {
            session()->flash('assign_message', 'The administrator account cannot be assigned as an officer.');
            return;
        }

        $officerRole = Role::where('role_name', 'Officer')->first();

        $user->organization_id = $this->assignOrganizationId;
        if ($officerRole) {
            $user->role_id = $officerRole->id; // <-- promote to Officer role
        }
        $user->save();

        OrganizationOfficer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'organization_id' => $this->assignOrganizationId,
                'officer_position_id' => $this->assignPositionId,
            ]
        );

        session()->flash('assign_message', trim($user->first_name . ' ' . $user->last_name) . ' is now an officer. Their organization is locked on their profile.');

        $this->reset(['selectedStudentId', 'assignOrganizationId', 'assignPositionId', 'studentSearch']);
    }

    public function removeOfficer(int $officerRecordId): void
    {
        $record = OrganizationOfficer::with('user')->findOrFail($officerRecordId);

        $studentRole = Role::where('role_name', 'Student')->first();
        if ($record->user && $studentRole) {
            $record->user->role_id = $studentRole->id; // <-- demote back to Student
            $record->user->save();
        }

        $record->delete();

        session()->flash('assign_message', 'Officer role removed. The student can change their organization again.');
    }

    public function getStudentResultsProperty()
    {
        if (strlen($this->studentSearch) < 2) {
            return collect();
        }

        return User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Student', 'Officer']))
            ->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->studentSearch . '%')
                    ->orWhere('last_name', 'like', '%' . $this->studentSearch . '%')
                    ->orWhere('student_number', 'like', '%' . $this->studentSearch . '%');
            })
            ->limit(8)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.organization-role-manager', [
            'organizations' => Organization::withCount('members', 'officers')->orderBy('name')->get(),
            'positions' => OfficerPosition::orderBy('sort_order')->orderBy('title')->get(),
            'programs' => Program::orderBy('level')->orderBy('name')->get(),
            'officers' => OrganizationOfficer::with(['user', 'organization', 'position'])->latest()->get(),
            'selectedStudent' => $this->selectedStudentId ? User::find($this->selectedStudentId) : null,
        ]);
    }
}