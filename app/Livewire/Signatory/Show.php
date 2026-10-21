<?php

namespace App\Livewire\Signatory;

use App\Enums\ApprovalStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\Workflow\WorkflowException;
use App\Models\TransactionApproval;
use App\Services\Workflow\SignatureStore;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\SigningRequest;
use App\Models\TransactionAuditLog;
use App\Models\CounselorAvailability;
use App\Services\Workflow\ExternalSigning;

/** One signing task. Approving re-checks the signer's password; rejecting needs a reason. */
class Show extends Component
{
    public int $approvalId;
    public ?string $modal = null; // 'approve' | 'reject'
    public string $password = '';
    public string $reason = '';
    public ?int $sendingId = null;
    public string $identityMethod = '';
    public string $identityNote = '';
    public bool $identityConfirmed = false;
    public ?int $editingId = null;
    public string $editEmail = '';
    public ?string $notice = null;
    public string $schedDate = '';
    public string $schedStart = '';
    public string $schedEnd = '';
    public string $inspectionRemarks = '';

    public ?string $rating = null;
        

    public function mount(TransactionApproval $approval): void
    {
        $user = Auth::user();
        $approval->loadMissing('workspace');

        abort_unless($user->isSignatory() || (int) $approval->acted_by === (int) $user->id, 403);
        abort_if((int) $approval->workspace->user_id === (int) $user->id, 403); // not your own document

        $mine = (int) $approval->acted_by === (int) $user->id;
        $holds = $approval->isAssignedTo($user);

        abort_unless($mine || ($holds && $approval->status === ApprovalStatus::Pending), 404);

        $this->approvalId = $approval->id;
        $v = $approval->workspace->field_values ?? [];
        $this->schedDate = (string) ($v['counseling_date'] ?? '');
        $this->schedStart = substr((string) ($v['counseling_time'] ?? ''), 0, 5);
        $this->schedEnd = substr((string) ($v['counseling_time_end'] ?? ''), 0, 5);
    }


    public function useSlot(int $slotId): void
    {
        $slot = CounselorAvailability::where('user_id', Auth::id())->whereKey($slotId)->firstOrFail();
        $this->schedDate = $slot->available_date->toDateString();
        $this->schedStart = substr($slot->start_time, 0, 5);
        $this->schedEnd = substr($slot->end_time, 0, 5);
    }

    public function saveSchedule(): void
    {
        $this->resetErrorBag();
        $this->validate([
            'schedDate' => ['required', 'date', 'after_or_equal:today'],
            'schedStart' => ['required', 'date_format:H:i'],
            'schedEnd' => ['nullable', 'date_format:H:i'],
        ], [], ['schedDate' => 'date', 'schedStart' => 'start time', 'schedEnd' => 'end time']);

        try {
            app(TransactionWorkflow::class)->setSchedule(
                $this->approval, Auth::user(), $this->schedDate, $this->schedStart, $this->schedEnd ?: null
            );
        } catch (WorkflowException $e) {
            $this->addError('schedule', $e->getMessage());
            return;
        }

        unset($this->approval);
        $this->notice = 'Schedule saved. The student has been notified.';
    }

    #[Computed]
    public function approval(): TransactionApproval
    {
        return TransactionApproval::with(['workspace.user', 'role'])->findOrFail($this->approvalId);
    }

    private function signatureReady(): bool
    {
        $u = Auth::user();
        if (! $u->signature_enabled) return true;   // signing without an image is allowed
        $path = $u->signature_path;
        return $path && Storage::disk(SignatureStore::DISK)->exists($path);
    }

    public function openModal(string $which): void
    {
        if (in_array($which, ['approve', 'reject'], true)) {
            $this->resetErrorBag();
            $this->inspectionRemarks = '';
            $this->password = '';
            $this->reason = '';
            $this->modal = $which;
            $this->rating = null;
        }
    }

    public function closeModal(): void
    {
        $this->modal = null;
        $this->password = '';
        $this->reason = '';
        $this->resetErrorBag();
        $this->inspectionRemarks = '';
    }

    public function approve()
    {
        $user = Auth::user();
        $key = 'sign-password:' . $user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('password', 'Too many wrong attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.');

            return;
        }

        $kind = $this->approval->signer_user_id ? ExternalSigning::kindFor($this->approval) : null;

        $this->validate([
            'password' => ['required', 'string'],
            'inspectionRemarks' => ['nullable', 'string', 'max:300'],
            'rating' => $kind === 'rating' ? ['required', 'in:1,2,3,4,5'] : ['nullable'],
        ], ['rating.required' => 'Choose a rating.'], ['password' => 'password', 'inspectionRemarks' => 'remarks']);

        if (! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($key, 60);
            $this->addError('password', 'That password is not correct.');

            return;
        }

        RateLimiter::clear($key);

        try {
            app(TransactionWorkflow::class)->approve($this->approval, $user, 'account_password', [
                'inspection_remarks' => $this->inspectionRemarks,
                'rating' => $this->rating,
            ]);
        } catch (WorkflowException $e) {
            $this->password = '';
            $this->addError('action', $e->getMessage());

            return;
        }

        $manual = DocumentRegistry::get($this->approval->workspace->document_type)->manualExternalInvites();

        session()->flash('signing_message', $manual
            ? 'Signed. Now send the report to the committee members below.'
            : 'Signed. The document moved to the next step.');

        return $manual
            ? $this->redirectRoute('signatory.show', ['approval' => $this->approvalId], navigate: true)
            : $this->redirectRoute('signatory.inbox', navigate: true);
    }

    public function reject()
    {
        try {
            app(TransactionWorkflow::class)->rejectApproval($this->approval, Auth::user(), $this->reason, 'account_password');
        } catch (WorkflowException $e) {
            $this->addError('action', $e->getMessage());

            return;
        }

        session()->flash('signing_message', 'Rejected. The student has been notified.');

        return $this->redirectRoute('signatory.inbox', navigate: true);
    }
    public function resendLink(int $approvalId): void
    {
        $this->resetErrorBag();

        try {
            app(TransactionWorkflow::class)->resendMemberLink(TransactionApproval::findOrFail($approvalId), Auth::user());
        } catch (WorkflowException $e) {
            $this->addError('send', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('send', 'The email could not be sent. Check the mail settings and try again.');

            return;
        }

        $this->notice = 'A new link was emailed. The previous link no longer works.';
    }

    public function render()
    {
        $approval = $this->approval;
        $ws = $approval->workspace;
        $user = Auth::user();
        $document = DocumentRegistry::get($ws->document_type);   // add this line

        $canAct = $approval->status === ApprovalStatus::Pending
            && $ws->workflow_status === TransactionStatus::UnderReview
            && $approval->isAssignedTo($user);
        $canSend = $document->manualExternalInvites()             // use it here
            && $ws->workflow_status === TransactionStatus::UnderReview
            && $ws->approvals()->where('signer_type', 'internal')
                ->where('status', ApprovalStatus::Approved->value)->where('acted_by', $user->id)->exists();

        $members = $canSend ? $ws->approvals()->where('signer_type', 'external')->get() : collect();

        $requests = $canSend
            ? SigningRequest::whereIn('approval_id', $members->pluck('id'))->latest('id')->get()->groupBy('approval_id')->map->first()
            : collect();
        $verified = $canSend
            ? TransactionAuditLog::where('workspace_id', $ws->workspace_id)
                ->whereIn('approval_id', $members->pluck('id'))
                ->whereIn('action', ['external.identity_checked', 'external.email_corrected'])
                ->orderBy('id')->get(['approval_id', 'action'])
                ->groupBy('approval_id')
                ->map(fn ($g) => $g->last()->action === 'external.identity_checked')
            : collect();
        $isScheduler = $document->signerSetsSchedule()
                && $approval->role?->key === $document->scheduleRoleKey();
                
        return view('livewire.signatory.show', [
            'approval' => $approval,
            'ws' => $ws,
            'document' => $document,                              // and here
            'approvals' => $ws->approvals()->with(['role', 'actor'])->get(),
            'canAct' => $canAct,
            'signatureReady' => $this->signatureReady(),
            'canSend' => $canSend, 'members' => $members, 'requests' => $requests,
            'verified' => $verified,

            'canSchedule' => $canAct && $isScheduler,
            'slots' => $canAct && $isScheduler
                ? CounselorAvailability::where('user_id', $user->id)->whereDate('available_date', '>=', today())
                    ->orderBy('available_date')->orderBy('start_time')->limit(6)->get()
                : collect(),
            'kind' => $approval->signer_user_id ? ExternalSigning::kindFor($approval) : null,
        ])->layout('layouts.app', ['title' => 'Sign document']);
    }
        public function openSend(int $approvalId): void
    {
        $this->resetErrorBag();
        $this->reset(['identityMethod', 'identityNote', 'identityConfirmed', 'editingId', 'editEmail']);
        $this->sendingId = $approvalId;
    }

    public function closeSend(): void
    {
        $this->reset(['sendingId', 'identityMethod', 'identityNote', 'identityConfirmed', 'editingId', 'editEmail']);
        $this->resetErrorBag();
    }

    public function startEdit(int $approvalId): void
    {
        $this->closeSend();
        $this->editingId = $approvalId;
        $this->editEmail = (string) TransactionApproval::whereKey($approvalId)->value('external_email');
    }

    public function saveEmail(): void
    {
        $this->resetErrorBag();

        try {
            app(TransactionWorkflow::class)->correctMemberEmail(
                TransactionApproval::findOrFail($this->editingId), Auth::user(), $this->editEmail
            );
        } catch (WorkflowException $e) {
            $this->addError('send', $e->getMessage());

            return;
        }

        $this->notice = 'Email corrected. Send the link again.';
        $this->closeSend();
    }

    public function sendLink(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'identityMethod' => ['required', 'in:' . implode(',', array_keys(TransactionWorkflow::IDENTITY_METHODS))],
            'identityNote' => ['nullable', 'string', 'max:300'],
            'identityConfirmed' => ['accepted'],
        ], [
            'identityMethod.required' => 'Choose how you verified this member.',
            'identityConfirmed.accepted' => 'Confirm that you verified this member.',
        ]);

        try {
            app(TransactionWorkflow::class)->sendMemberLink(
                TransactionApproval::findOrFail($this->sendingId),
                Auth::user(),
                ['method' => $this->identityMethod, 'note' => trim($this->identityNote) ?: null, 'confirmed' => true]
            );
        } catch (WorkflowException $e) {
            $this->addError('send', $e->getMessage());

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('send', 'The email could not be sent. Check the mail settings and try again.');

            return;
        }

        $this->notice = 'Link emailed. The member confirms their email with a one-time code before they can see the report.';
        $this->closeSend();
    }
}
