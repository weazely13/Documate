<?php

namespace App\Livewire\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class ManageUsers extends Component
{
    use WithPagination;

    // Tabs: 'users' (existing table) | 'org-roles' (new organization/role manager)
    public string $activeTab = 'users';

    public $search = '';
    public $roleFilter = '';
    public $statusFilter = '';
    public string $yearFilter = '';

    public $selectedUser = null;
    public $showModal = false;
    public $userPendingDeletion = null;
    public bool $showDeleteModal = false;

    protected $paginationTheme = 'tailwind';

    public function updatingSearch() { $this->resetPage(); }
    public function updatingRoleFilter() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingYearFilter() { $this->resetPage(); }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['users', 'org-roles'], true) ? $tab : 'users';
    }

    public function openModal($userId)
    {
        $this->selectedUser = User::find($userId);
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->selectedUser = null;
    }

    public function confirmDelete(int $userId): void
    {
        $user = User::with('role')->findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('message', 'You cannot delete your own account.');

            return;
        }

        if ($this->isAdmin($user)) {
            session()->flash('message', 'The administrator account cannot be deleted.');

            return;
        }

        $this->userPendingDeletion = $user;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->userPendingDeletion = null;
    }

    public function deleteUser(): void
    {
        if (! $this->userPendingDeletion) {
            return;
        }

        $user = User::with('role')->find($this->userPendingDeletion->id);

        if (! $user) {
            $this->cancelDelete();
            session()->flash('message', 'User record was already removed.');

            return;
        }

        if ($user->id === auth()->id()) {
            $this->cancelDelete();
            session()->flash('message', 'You cannot delete your own account.');

            return;
        }

        if ($this->isAdmin($user)) {
            $this->cancelDelete();
            session()->flash('message', 'The administrator account cannot be deleted.');

            return;
        }

        $deletedName = $this->displayName($user);

        if ($this->selectedUser?->id === $user->id) {
            $this->closeModal();
        }

        $user->delete();
        $this->cancelDelete();
        $this->resetPageIfEmpty();

        session()->flash('message', $deletedName . ' was removed successfully.');
    }

    public function updateRole($userId, $roleId)
    {
        $user = User::with('role')->findOrFail($userId);
        $targetRole = Role::findOrFail($roleId);

        if ($user->id === auth()->id()) {
            session()->flash('message', 'You cannot change your own role.');
            return;
        }

        // The admin account itself can never be touched...
        if ($this->isAdmin($user)) {
            session()->flash('message', 'The administrator account cannot be modified.');
            return;
        }

        // ...and no other account may ever become the admin.
        if (Str::lower($targetRole->role_name) === 'admin') {
            session()->flash('message', 'Only one administrator account is allowed. You cannot assign the Admin role to another user.');
            return;
        }

        $user->role_id = $roleId;
        $user->save();

        session()->flash('message', 'Role updated.');
    }

    public function toggleStatus($userId)
    {
        $user = User::with('role')->findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('message', 'You cannot disable your own account.');
            return;
        }

        if ($this->isAdmin($user)) {
            session()->flash('message', 'The administrator account cannot be modified.');
            return;
        }

        $user->account_status = $user->account_status === 'active' ? 'inactive' : 'active';
        $user->save();

        session()->flash('message', 'Status updated.');
    }

    protected function isAdmin(User $user): bool
    {
        return Str::lower((string) ($user->role->role_name ?? '')) === 'admin';
    }

    protected function isLastAdmin(User $user): bool
    {
        if (! $this->isAdmin($user)) {
            return false;
        }

        return User::query()
            ->whereHas('role', function ($query) {
                $query->where('role_name', 'Admin');
            })
            ->count() <= 1;
    }

    protected function displayName(User $user): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $user->first_name,
            $user->middle_name,
            $user->last_name,
        ])))) ?: ('User #' . $user->id);
    }

    protected function resetPageIfEmpty(): void
    {
        $currentPage = $this->getPage();

        if ($currentPage > 1 && ! $this->getUsersQuery()->forPage($currentPage, 30)->exists()) {  // was 15
            $this->previousPage();
        }
    }

    protected function getUsersQuery()
    {
        return User::with(['role', 'organization', 'program', 'officerRecord.position'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('middle_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%')
                    ->orWhere('student_number', 'like', '%' . $this->search . '%')
                    ->orWhere('college', 'like', '%' . $this->search . '%')
                    ->orWhere('year_level', 'like', '%' . $this->search . '%')
                    ->orWhere('academic_status', 'like', '%' . $this->search . '%')
                    ->orWhere('account_status', 'like', '%' . $this->search . '%')
                    ->orWhereHas('role', function ($q2) {
                        $q2->where('role_name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('organization', function ($q3) {
                        $q3->where('name', 'like', '%' . $this->search . '%');
                    })
                    ->orWhereHas('program', function ($q4) {
                        $q4->where('name', 'like', '%' . $this->search . '%');
                    });
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->where('role_id', $this->roleFilter);
            })
            ->when($this->statusFilter !== '', function ($query) {
                $status = $this->statusFilter == '1' ? 'active' : 'inactive';
                $query->where('account_status', $status);
            })
            ->when($this->yearFilter !== '', function ($query) {
                $query->where('year_level', $this->yearFilter);
            })
            ->orderByRaw("CASE WHEN year_level REGEXP '^[0-9]+$' THEN CAST(year_level AS UNSIGNED) ELSE 999 END ASC")
            ->orderBy('last_name')
            ->orderBy('first_name');
    }

    public function render()
    {
        $users = $this->getUsersQuery()->paginate(30);

        $roles = Role::orderBy('role_name')->get();

        return view('livewire.admin.manage-users', [
            'users' => $users,
            'roles' => $roles
        ])->layout('layouts.app', ['title' => 'Manage Users']);
    }
}