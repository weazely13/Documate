<?php

namespace App\Services\Workflow;

use App\Enums\ApprovalStatus;
use App\Enums\TransactionStatus;
use App\Events\Workflow\ApprovalRequested;
use App\Events\Workflow\TransactionCompleted;
use App\Exceptions\Workflow\WorkflowException;
use App\Models\SignatoryRole;
use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use App\Models\TransactionApproval;
use App\Models\TransactionAuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Notification;
use App\Services\Workflow\Seal;
use App\Services\Workflow\SignatureStore;
use App\Models\SigningRequest;
use Illuminate\Support\Str;
use App\Services\Workflow\SignatoryScope;
use App\Workflow\Documents\WorkflowDocument;
use Illuminate\Support\Facades\Storage;

/**
 * The single place where a workflow transaction changes state.
 *
 * Rules:
 *  - Livewire components / controllers never write workflow_status or approvals directly.
 *  - Every public method runs in a DB transaction with the workspace row locked, so two people
 *    acting at once (e.g. two counselors) cannot both win. The second gets a WorkflowException.
 *  - Every state change writes an audit log row.
 *  - Methods return the fresh workspace; the instance you passed in is stale after the call.
 */

class TransactionWorkflow
{
    /* ------------------------------------------------------------------ *
     * Student
     * ------------------------------------------------------------------ */

    /** Turn a freshly created workspace into a DRAFT workflow transaction (idempotent). */
    /** How the VPSD reviewer verified an external signer. Shown in the select box and stored in the audit row. */
    public const IDENTITY_METHODS = [
        'directory' => 'Found in the faculty/staff directory or an official roster',
        'official_contact' => 'Confirmed by phone or email using official records (not the student\'s)',
        'in_person' => 'Confirmed in person',
        'vouched' => 'Vouched for by the dean, adviser or an officer of the organization',
        'other' => 'Other (explain in the note)',
    ];
    public function initialize(StudentDocumentWorkspace $workspace): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace) {
            $ws = $this->lock($workspace->getKey());

            if ($ws->workflow_status !== null) {
                return $ws;
            }

            if (! DocumentRegistry::has($ws->document_type)) {
                throw WorkflowException::templateNotWorkflowEnabled();
            }

            $ws->forceFill([
                'transaction_code' => $this->nextTransactionCode(),
                'workflow_status' => TransactionStatus::Draft,
            ])->save();

            $this->audit($ws, $ws->user, 'transaction.created', null, TransactionStatus::Draft);

            return $ws;
        });
    }


    /** DRAFT -> SUBMITTED. Validate the form fields in the component BEFORE calling this. */
    public function submit(StudentDocumentWorkspace $workspace, User $student): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace, $student) {
            $ws = $this->lock($workspace->getKey());
            $this->assertOwner($ws, $student);

            $from = $this->transition($ws, TransactionStatus::Submitted, [
                'submitted_at' => now(),
                'workflow_note' => null,
            ]);
            $this->audit($ws, $student, 'transaction.submitted', $from, TransactionStatus::Submitted);

            return $ws;
        });
    }

    /** ACTION_REQUIRED -> UNDER_REVIEW, after the student has corrected the form. */
    public function respondToActionRequest(StudentDocumentWorkspace $workspace, User $student): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace, $student) {
            $ws = $this->lock($workspace->getKey());
            $this->assertOwner($ws, $student);

            $from = $this->transition($ws, TransactionStatus::UnderReview, ['workflow_note' => null]);
            $this->audit($ws, $student, 'student.responded', $from, TransactionStatus::UnderReview);

            return $ws;
        });
    }

    /* ------------------------------------------------------------------ *
     * VPSD reviewer
     * ------------------------------------------------------------------ */

    public function startReview(StudentDocumentWorkspace $workspace, User $reviewer): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace, $reviewer) {
            $this->assertReviewer($reviewer);
            $ws = $this->lock($workspace->getKey());

            $from = $this->transition($ws, TransactionStatus::UnderReview);
            $this->audit($ws, $reviewer, 'review.started', $from, TransactionStatus::UnderReview);

            return $ws;
        });
    }

    /**
     * UNDER_REVIEW -> ACTION_REQUIRED. Only allowed while nobody has signed yet; any signing
     * tasks already opened are removed and are recreated fresh when the reviewer accepts again.
     */
    public function requestAction(StudentDocumentWorkspace $workspace, User $reviewer, string $message): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace, $reviewer, $message) {
            $this->assertReviewer($reviewer);
            $message = $this->cleanReason($message);
            $ws = $this->lock($workspace->getKey());

            if (DocumentRegistry::get($ws->document_type)->issuedByOffice()) {
                throw WorkflowException::officeIssuedNotEditable();
            }
            if ($ws->approvals()->where('status', ApprovalStatus::Approved->value)->exists()) {
                throw WorkflowException::approvalsAlreadyActed();
            }

            $removed = $ws->approvals()->whereIn('status', ApprovalStatus::openValues())->delete();

            $from = $this->transition($ws, TransactionStatus::ActionRequired, ['workflow_note' => $message]);
            $this->audit($ws, $reviewer, 'review.action_requested', $from, TransactionStatus::ActionRequired, [
                'message' => $message,
                'signing_tasks_removed' => $removed,
            ]);

            return $ws;
        });
    }
    /**
     * COMPLETED -> VOIDED. Optionally creates a corrected DRAFT for the same student with the answers copied in.
     * The student must resubmit it: a changed document needs fresh review and fresh signatures.
     */
    public function void(StudentDocumentWorkspace $workspace, User $reviewer, string $reason, bool $reissue = true): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace, $reviewer, $reason, $reissue) {
            $this->assertReviewer($reviewer);
            $reason = $this->cleanReason($reason);
            $ws = $this->lock($workspace->getKey());
            $reissue = $reissue && ! DocumentRegistry::get($ws->document_type)->issuedByOffice();
            $replacement = null;

            if ($reissue) {
                $replacement = new StudentDocumentWorkspace();
                $replacement->forceFill([
                    'user_id' => $ws->user_id,
                    'document_type' => $ws->document_type,
                    'field_values' => \Illuminate\Support\Arr::except($ws->field_values ?? [], ['student_signature_path', 'student_signature_hash']),
                    'status' => 'pending', // legacy column; the workflow state lives in workflow_status
                    'replaces_workspace_id' => $ws->workspace_id,
                ])->save();

                $replacement = $this->initialize($replacement);
            }

            $from = $this->transition($ws, TransactionStatus::Voided, [
                'voided_at' => now(),
                'voided_by' => $reviewer->id,
                'void_reason' => $reason,
                'superseded_by' => $replacement?->workspace_id,
            ]);

            $this->audit($ws, $reviewer, 'document.voided', $from, TransactionStatus::Voided, [
                'reason' => $reason,
                'replacement' => $replacement?->transaction_code,
            ]);

            if ($replacement) {
                $this->audit($replacement, $reviewer, 'transaction.reissued', null, TransactionStatus::Draft, [
                    'replaces' => $ws->transaction_code,
                ]);
            }

            return $ws;
        });
    }

    /** Reject the whole transaction at review level. */
    public function reject(StudentDocumentWorkspace $workspace, User $reviewer, string $reason): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace, $reviewer, $reason) {
            $this->assertReviewer($reviewer);
            $reason = $this->cleanReason($reason);
            $ws = $this->lock($workspace->getKey());

            $this->skipOpenApprovals($ws);

            $from = $this->transition($ws, TransactionStatus::Rejected, ['workflow_note' => $reason]);
            $this->audit($ws, $reviewer, 'review.rejected', $from, TransactionStatus::Rejected, ['reason' => $reason]);

            return $ws;
        });
    }

    /**
     * Reviewer is satisfied: open the template's approval chain. Step 1 becomes PENDING, the
     * rest WAITING. A template with no steps goes straight to APPROVED.
     */
    public function acceptForSigning(StudentDocumentWorkspace $workspace, User $reviewer, ?array $identityCheck = null, array $reviewerValues = []): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace, $reviewer, $identityCheck, $reviewerValues) {
            $this->assertCanOpenChain($reviewer, $workspace);
            $ws = $this->lock($workspace->getKey());

            if ($ws->workflow_status !== TransactionStatus::UnderReview) {
                throw WorkflowException::invalidTransition(
                    $ws->workflow_status ?? TransactionStatus::Draft,
                    TransactionStatus::UnderReview
                );
            }

            if ($ws->approvals()->exists()) {
                throw WorkflowException::signingAlreadyStarted();
            }

            $values = $ws->field_values ?? [];
            $ws->loadMissing('user.program');

            $doc = DocumentRegistry::get($ws->document_type);
            if ($doc->reviewerFields()) {
                foreach ($doc->reviewerFields() as $name => $f) {
                    $val = trim((string) ($reviewerValues[$name] ?? ''));

                    if ($val === '' && ($f['required'] ?? false)) {
                        throw WorkflowException::reviewerFieldRequired($f['label']);
                    }
                    if ($val !== '' && ($f['type'] ?? 'text') === 'date' && ! strtotime($val)) {
                        throw WorkflowException::reviewerFieldRequired($f['label']);
                    }
                    if ($val !== '' && ($f['type'] ?? 'text') === 'time' && ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $val)) {
                        throw WorkflowException::reviewerFieldRequired($f['label']);
                    }

                    $values[$name] = $val === '' ? null : $val;
                }

                $ws->forceFill(['field_values' => $values])->save();
            }
            $values = $doc->beforeSigning($values);
            if ($values !== ($ws->field_values ?? [])) {
                $ws->forceFill(['field_values' => $values])->save();
            }

            $steps = collect(DocumentRegistry::get($ws->document_type)->chain($values))->values()->map(function ($s, $i) use ($values, $ws) {
                if (($s['type'] ?? 'internal') === 'external') {
                    $email = Str::lower(trim((string) ($values[$s['email_field']] ?? '')));
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        throw WorkflowException::externalEmailMissing();
                    }

                    return (object) [
                        'id' => null, 'step_order' => $i + 1, 'label' => $s['label'],
                        'signatory_role_id' => null, 'role' => null,
                        'external' => true,
                        'name' => trim((string) ($values[$s['name_field']] ?? '')) ?: null,
                        'email' => $email,
                    ];
                }

                $role = SignatoryRole::where('key', $s['role'])->first();
                if (! $role) {
                    throw new WorkflowException("Signatory role \"{$s['role']}\" does not exist. Run SignatoryRoleSeeder.");
                }

                $scopeType = $s['scope'] ?? null;
                if (($scopeType ?? 'none') !== $role->scope && ! ($scopeType === null && $role->scope === 'none')) {
                    throw new WorkflowException("Role \"{$role->label}\" is set up with scope \"{$role->scope}\" but this form needs \"" . ($scopeType ?? 'none') . '".');
                }

                $scopeId = null;
                if ($scopeType) {
                    $scopeId = ! empty($s['scope_id'])
                        ? (int) $s['scope_id']
                        : (SignatoryScope::resolve($scopeType, $ws->user) ?: throw WorkflowException::scopeMissing($scopeType));
                }

                return (object) [
                    'id' => null, 'step_order' => $i + 1, 'label' => $s['label'],
                    'signatory_role_id' => $role->id, 'role' => $role,
                    'external' => false, 'name' => null, 'email' => null,
                    'scope_type' => $scopeType, 'scope_id' => $scopeId,
                ];
            });

            // Refuse to open a chain that would stall because nobody holds a role.
            foreach ($steps as $step) {
                if ($step->role && ! $step->role->holdersFor($step->scope_type ?? null, $step->scope_id ?? null)->exists()) {
                    $where = ($step->scope_type ?? null)
                        ? ' (' . SignatoryScope::name($step->scope_type, $step->scope_id) . ')'
                        : '';
                    throw WorkflowException::noHolderForRole($step->role->label . $where);
                }
            }
            $needsCheck = ! $doc->manualExternalInvites() && $steps->contains(fn ($s) => $s->external);

            if ($needsCheck) {
                $method = $identityCheck['method'] ?? null;

                if (! array_key_exists($method, self::IDENTITY_METHODS) || empty($identityCheck['confirmed'])) {
                    throw WorkflowException::identityCheckRequired();
                }
            }

            if ($steps->isEmpty()) {
                $this->audit($ws, $reviewer, 'review.accepted', TransactionStatus::UnderReview, null, ['steps' => 0]);
                $from = $this->transition($ws, TransactionStatus::Approved);
                $this->audit($ws, $reviewer, 'approvals.completed', $from, TransactionStatus::Approved);

                return $ws;
            }

            $first = null;
            foreach ($steps as $i => $step) {
                $approval = new TransactionApproval();
                $approval->forceFill([
                    'workspace_id' => $ws->workspace_id,
                    'template_signatory_step_id' => $step->id,
                    'step_order' => $step->step_order,
                    'signatory_role_id' => $step->signatory_role_id,
                    'label' => $step->label,
                    'signer_type' => $step->external ? 'external' : 'internal',
                    'external_name' => $step->name,
                    'external_email' => $step->email,
                    'status' => $i === 0 ? ApprovalStatus::Pending : ApprovalStatus::Waiting,
                    'opened_at' => $i === 0 ? now() : null,
                    'scope_type' => $step->scope_type ?? null,
                    'scope_id' => $step->scope_id ?? null,
                ])->save();
                $first ??= $approval;
            }
            if ($needsCheck) {
                $externalApproval = $ws->approvals()->where('signer_type', 'external')->first();

                $this->audit($ws, $reviewer, 'external.identity_checked', null, null, [
                    'method' => $identityCheck['method'],
                    'note' => $identityCheck['note'] ?? null,
                    'rater' => $externalApproval?->external_name,
                    'rater_email' => \App\Services\Workflow\ExternalSigning::mask((string) $externalApproval?->external_email),
                ], $externalApproval?->id);
            }
            $this->audit($ws, $reviewer, 'review.accepted', TransactionStatus::UnderReview, null, [
                'steps' => $steps->map(fn ($s) => "{$s->step_order}. {$s->label} (" . ($s->role->key ?? 'external')
                    . (($s->scope_type ?? null) ? ", {$s->scope_type}#{$s->scope_id}" : '') . ')')->all(),
            ]);

            DB::afterCommit(fn () => ApprovalRequested::dispatch($first));

            return $ws;
        });
    }

    /** APPROVED -> COMPLETED: the final verification. Issues the transaction. */
    public function complete(StudentDocumentWorkspace $workspace, User $reviewer): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($workspace, $reviewer) {
            $this->assertReviewer($reviewer);
            $ws = $this->lock($workspace->getKey());

            if ($ws->approvals()->where('status', '!=', ApprovalStatus::Approved->value)->exists()) {
                throw WorkflowException::approvalsIncomplete();
            }

            $from = $this->transition($ws, TransactionStatus::Completed, [
                'completed_at' => now(),
                'issued_at' => now(),
            ]);
            $this->audit($ws, $reviewer, 'transaction.completed', $from, TransactionStatus::Completed);

            DB::afterCommit(fn () => TransactionCompleted::dispatch($ws));

            return $ws;
        });
    }

    /**
     * VPSD issues a document TO a student. The student never drafts or submits it:
     * the record is created already UNDER_REVIEW and the approval chain opens immediately.
     * One transaction: if nobody holds the signing role, nothing is left behind.
     */
    public function issueForStudent(User $issuer, User $student, string $type, array $values): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($issuer, $student, $type, $values) {
            $doc = DocumentRegistry::find($type);
            if (! $doc || ! $doc->issuedByOffice()) {
                throw WorkflowException::notOfficeDocument();
            }
            $this->assertCanIssue($issuer, $doc);

            $student->loadMissing('role');
            if (! in_array($student->role?->role_name, ['Student', 'Officer'], true) || $student->account_status !== 'active') {
                throw WorkflowException::studentNotEligible();
            }
            if ((int) $issuer->id === (int) $student->id) {
                throw WorkflowException::cannotIssueToSelf();
            }

            $answers = [];
            foreach ($doc->fields() as $name => $f) {
                $kind = $f['type'] ?? 'text';
                $raw = $values[$name] ?? null;

                if ($kind === 'checkbox') {
                    $answers[$name] = (bool) $raw;
                    continue;
                }

                $val = is_string($raw) ? trim($raw) : $raw;
                $val = $val === '' ? null : $val;

                if ($val === null) {
                    if ($f['required'] ?? false) {
                        throw WorkflowException::reviewerFieldRequired($f['label']);
                    }
                } else {
                    if ($kind === 'date' && ! strtotime((string) $val)) {
                        throw WorkflowException::reviewerFieldRequired($f['label']);
                    }
                    if ($kind === 'time' && ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $val)) {
                        throw WorkflowException::reviewerFieldRequired($f['label']);
                    }
                    if (isset($f['max']) && mb_strlen((string) $val) > $f['max']) {
                        throw new WorkflowException("{$f['label']} is too long (maximum {$f['max']} characters).");
                    }
                }

                $answers[$name] = $val;
            }

            $answers = $doc->beforeIssue($answers);
            if ($message = $doc->check($answers)) {
                throw new WorkflowException($message);
            }

            $snapshot = [
                'student_name' => FormSupport::systemValue($student, 'formatted_full_name'),
                'student_program' => FormSupport::systemValue($student, 'program_year_section'),
                'student_course' => FormSupport::systemValue($student, 'program'),
                'student_number' => FormSupport::systemValue($student, 'student_number'),
            ];
            
            if ($snapshot['student_name'] === '') {
                throw WorkflowException::studentNameMissing();
            }

            if ($doc->issuedBySignatory()) {
                $snapshot += $this->reporterSnapshot($issuer);
            }

            $ws = new StudentDocumentWorkspace();
            $ws->forceFill([
                'user_id' => $student->id,
                'document_type' => $type,
                'field_values' => $answers + $snapshot,
                'status' => 'pending', // legacy column
                'transaction_code' => $this->nextTransactionCode(),
                'workflow_status' => TransactionStatus::UnderReview,
                'submitted_at' => now(),
            ])->save();

            $this->audit($ws, $issuer, $doc->issuerIsStaff() ? 'transaction.issued_by_role' : 'transaction.issued_by_office', null, TransactionStatus::UnderReview, [
                'student' => $snapshot['student_name'],
                'document' => $doc->name(),
            ]);

            $ws = $this->acceptForSigning($ws, $issuer);

            $wsId = $ws->workspace_id;
            DB::afterCommit(function () use ($wsId) {
                try {
                    $fresh = StudentDocumentWorkspace::with('user')->find($wsId);
                    $fresh?->user?->notify(WorkflowNotification::forIssued($fresh));
                } catch (\Throwable $e) {
                    report($e);
                }
            });

            return $ws;
        });
    }

    /* ------------------------------------------------------------------ *
     * Signatory
     * ------------------------------------------------------------------ */

    /**
     * Approve one step. The first holder of the role to act wins; anyone else who tries
     * afterwards gets approvalNotActionable().
     *
     * Re-check the signer's password in the component before calling this.
     */
    public function approve(TransactionApproval $approval, User $signer, string $authMethod = 'account'): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($approval, $signer, $authMethod) {
            $ws = $this->lock($approval->workspace_id);
            $approval = $this->lockApproval($approval->id);
            $this->assertActionable($ws, $approval, $signer);

            $doc = DocumentRegistry::get($ws->document_type);
            if ($doc->signerSetsSchedule() && empty($ws->field_values['counseling_date'])) {
                throw new WorkflowException('Set the counselling date and time before signing.');
            }

            // ONE save. The model's saving hook archives the signature, fills the name and hashes, and seals it.
            $approval->forceFill([
                'status' => ApprovalStatus::Approved,
                'acted_by' => $signer->id,
                'auth_method' => $authMethod,
                'signature_path' => $signer->signature_path,
                'acted_at' => now(),
            ])->save();

            $this->audit($ws, $signer, 'approval.approved', $ws->workflow_status, $ws->workflow_status, [
                'step' => $approval->step_order,
                'label' => $approval->label,
                'role' => $approval->role->key,
                'auth_method' => $authMethod,
            ], $approval->id);

            return $this->advance($ws, $signer);
        });
    }
    /** The signer (Guidance Counselor) sets the counselling date and time. Allowed only while their step is open. */
    public function setSchedule(TransactionApproval $approval, User $signer, string $date, ?string $time, ?string $timeEnd): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($approval, $signer, $date, $time, $timeEnd) {
            $ws = $this->lock($approval->workspace_id);
            $approval = $this->lockApproval($approval->id);
            $this->assertActionable($ws, $approval, $signer);

            if (! DocumentRegistry::get($ws->document_type)->signerSetsSchedule()) {
                throw WorkflowException::approvalNotActionable();
            }
            if (! strtotime($date) || \Carbon\Carbon::parse($date)->lt(today())) {
                throw new WorkflowException('Choose today or a later date.');
            }
            foreach ([$time, $timeEnd] as $t) {
                if ($t && ! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t)) {
                    throw new WorkflowException('Enter a valid time.');
                }
            }
            if ($time && $timeEnd && $timeEnd <= $time) {
                throw new WorkflowException('The end time must be after the start time.');
            }

            $values = $ws->field_values ?? [];
            $values['counseling_date'] = \Carbon\Carbon::parse($date)->toDateString();
            $values['counseling_time'] = $time ?: null;
            $values['counseling_time_end'] = $timeEnd ?: null;
            $ws->forceFill(['field_values' => $values])->save();

            $this->audit($ws, $signer, 'schedule.set', null, null, [
                'message' => DocumentRegistry::get($ws->document_type)->issuedMessage($values),
            ], $approval->id);

            $wsId = $ws->workspace_id;
            DB::afterCommit(function () use ($wsId) {
                try {
                    $fresh = StudentDocumentWorkspace::with('user')->find($wsId);
                    $fresh?->user?->notify(WorkflowNotification::forSchedule($fresh));
                } catch (\Throwable $e) {
                    report($e);
                }
            });

            return $ws;
        });
    }

    /** Reject at a signing step. Ends the whole transaction (REJECTED). */
    public function rejectApproval(TransactionApproval $approval, User $signer, string $reason, string $authMethod = 'account'): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($approval, $signer, $reason, $authMethod) {
            $reason = $this->cleanReason($reason);
            $ws = $this->lock($approval->workspace_id);
            $approval = $this->lockApproval($approval->id);
            $this->assertActionable($ws, $approval, $signer);

            $approval->forceFill([
                'status' => ApprovalStatus::Rejected,
                'acted_by' => $signer->id,
                'auth_method' => $authMethod,
                'reason' => $reason,
                'acted_at' => now(),
            ])->save();

            $this->skipOpenApprovals($ws);

            $from = $this->transition($ws, TransactionStatus::Rejected, ['workflow_note' => $reason]);
            $this->audit($ws, $signer, 'approval.rejected', $from, TransactionStatus::Rejected, [
                'step' => $approval->step_order,
                'label' => $approval->label,
                'role' => $approval->role->key,
                'reason' => $reason,
            ], $approval->id);

            return $ws;
        });
    }
    protected function assertCanIssue(User $user, WorkflowDocument $doc): void
    {
        if (! $doc->canBeIssuedBy($user)) {
            throw WorkflowException::notIssuer();
        }
    }

    protected function assertCanOpenChain(User $user, StudentDocumentWorkspace $ws): void
    {
        if ($this->canReview($user)) {
            return;
        }

        $doc = DocumentRegistry::find($ws->document_type);
        if ($doc && $doc->issuerIsStaff() && $doc->canBeIssuedBy($user)) {
            return;
        }

        throw WorkflowException::notReviewer();
    }

    /**
     * One-click resend for a member whose first email failed or expired.
     * Allowed only if the Chairman already recorded the identity check and hasn't changed the email since.
     */
    public function resendMemberLink(TransactionApproval $approval, User $sender): void
    {
        DB::transaction(function () use ($approval, $sender) {
            $ws = $this->lock($approval->workspace_id);
            $approval = $this->lockApproval($approval->id);
            $this->assertMemberSender($ws, $approval, $sender);

            $last = TransactionAuditLog::where('workspace_id', $ws->workspace_id)
                ->where('approval_id', $approval->id)
                ->whereIn('action', ['external.identity_checked', 'external.email_corrected'])
                ->orderByDesc('id')
                ->value('action');

            if ($last !== 'external.identity_checked') {
                throw WorkflowException::identityCheckRequired();
            }
        });

        // Outside the transaction. Revokes the old link and throws if the mail fails.
        app(ExternalSigning::class)->invite($approval->fresh());
    }
    /** The external signer approved. Called only by the public signing page after the emailed code was verified. */
   public function approveExternal(
        SigningRequest $request,
        array $response,
        string $signerName,
        string $signaturePath,
        string $signatureHash, // kept for the caller; the model's saving hook recomputes it
    ): StudentDocumentWorkspace {
        return DB::transaction(function () use ($request, $response, $signerName, $signaturePath) {
            $ws = $this->lock($request->workspace_id);
            $approval = $this->lockApproval($request->approval_id);
            $request = SigningRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $this->assertExternalActionable($ws, $approval, $request);

            // One save only. The model's saving hook archives the signature, sets the hashes and the seal.
            $approval->forceFill([
                'status' => ApprovalStatus::Approved,
                'acted_by' => null,
                'auth_method' => 'email_code',
                'signature_path' => $signaturePath,
                'signer_name' => $signerName,
                'response' => $response,
                'acted_at' => now(),
            ])->save();

            $request->forceFill(['status' => 'completed', 'completed_at' => now()])->save();

            $this->audit($ws, null, 'approval.approved', $ws->workflow_status, $ws->workflow_status, [
                'step' => $approval->step_order,
                'label' => $approval->label,
                'role' => 'external',
                'auth_method' => 'email_code',
                'signer' => $signerName,
            ], $approval->id);

            return $this->advance($ws, null);
        });
    }
    /** The external signer declined. Ends the whole transaction (REJECTED), same as an internal rejection. */
    public function declineExternal(SigningRequest $request, string $reason, string $signerName): StudentDocumentWorkspace
    {
        return DB::transaction(function () use ($request, $reason, $signerName) {
            $reason = $this->cleanReason($reason);
            $ws = $this->lock($request->workspace_id);
            $approval = $this->lockApproval($request->approval_id);
            $request = SigningRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            $this->assertExternalActionable($ws, $approval, $request);

            $approval->forceFill([
                'status' => ApprovalStatus::Rejected,
                'acted_by' => null,
                'auth_method' => 'email_code',
                'reason' => $reason,
                'signer_name' => $signerName ?: $approval->external_name,
                'acted_at' => now(),
            ])->save();

            $request->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
            $this->skipOpenApprovals($ws);

            $from = $this->transition($ws, TransactionStatus::Rejected, ['workflow_note' => $reason]);
            $this->audit($ws, null, 'approval.rejected', $from, TransactionStatus::Rejected, [
                'step' => $approval->step_order,
                'label' => $approval->label,
                'role' => 'external',
                'reason' => $reason,
            ], $approval->id);

            return $ws;
        });
    }

    /** Audit rows for events that have no logged-in user (invitation sent, code sent, identity confirmed). */
    public function recordExternalEvent(StudentDocumentWorkspace $workspace, string $action, array $meta = [], ?int $approvalId = null): void
    {
        DB::transaction(function () use ($workspace, $action, $meta, $approvalId) {
            $this->lock($workspace->getKey());
            $this->audit($workspace, null, $action, null, null, $meta, $approvalId);
        });
    }
    /* ------------------------------------------------------------------ *
     * Extra audit events called from other parts of the app
     * ------------------------------------------------------------------ */

    public function recordDocumentGenerated(StudentDocumentWorkspace $workspace, string $path): void
    {
        DB::transaction(function () use ($workspace, $path) {
            $this->lock($workspace->getKey());
            $this->audit($workspace, null, 'document.generated', null, null, ['path' => $path]);
        });
    }

    public function recordDownload(StudentDocumentWorkspace $workspace, User $actor): void
    {
        DB::transaction(function () use ($workspace, $actor) {
            $this->lock($workspace->getKey());
            $this->audit($workspace, $actor, 'document.downloaded');
        });
    }

    /* ------------------------------------------------------------------ *
     * Internals
     * ------------------------------------------------------------------ */

    /** Who counts as a VPSD reviewer. Swap this one method if you later add a dedicated role. */
    protected function canReview(User $user): bool
    {
        return $user->role?->role_name === 'Admin';
    }

    protected function assertReviewer(User $user): void
    {
        if (! $this->canReview($user)) {
            throw WorkflowException::notReviewer();
        }
    }

    protected function assertOwner(StudentDocumentWorkspace $ws, User $user): void
    {
        if ((int) $ws->user_id !== (int) $user->id) {
            throw WorkflowException::notOwner();
        }
    }

    protected function assertActionable(StudentDocumentWorkspace $ws, TransactionApproval $approval, User $signer): void
    {
        if ((int) $ws->user_id === (int) $signer->id) {
            throw WorkflowException::selfApproval();
        }
        if ($ws->workflow_status !== TransactionStatus::UnderReview
            || $approval->status !== ApprovalStatus::Pending) {
            throw WorkflowException::approvalNotActionable();
        }

        if (! $approval->isAssignedTo($signer)) {
            throw WorkflowException::notSignatory();
        }
    }

    protected function cleanReason(string $reason): string
    {
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw WorkflowException::reasonRequired();
        }

        return $reason;
    }
    protected function assertExternalActionable(StudentDocumentWorkspace $ws, TransactionApproval $approval, SigningRequest $request): void
    {
        if ($request->status !== 'pending' || $request->expires_at->isPast() || (int) $request->approval_id !== (int) $approval->id) {
            throw WorkflowException::signingRequestInvalid();
        }

        if ($approval->signer_type !== 'external'
            || $ws->workflow_status !== TransactionStatus::UnderReview
            || $approval->status !== ApprovalStatus::Pending) {
            throw WorkflowException::approvalNotActionable();
        }
    }
    /**
     * After a step is approved: do nothing while parallel peers are still pending; otherwise open the
     * next waiting step (or all waiting external steps together, for manual-invite documents);
     * or finish with APPROVED when nothing is left.
     */
    protected function advance(StudentDocumentWorkspace $ws, ?User $actor): StudentDocumentWorkspace
    {
        if ($ws->approvals()->where('status', ApprovalStatus::Pending->value)->exists()) {
            return $ws;
        }

        $next = $ws->approvals()->where('status', ApprovalStatus::Waiting->value)->first();

        if (! $next) {
            $from = $this->transition($ws, TransactionStatus::Approved);
            $this->audit($ws, $actor, 'approvals.completed', $from, TransactionStatus::Approved);

            return $ws;
        }

        $parallel = $next->signer_type === 'external'
            && DocumentRegistry::get($ws->document_type)->manualExternalInvites();

        $toOpen = $parallel
            ? $ws->approvals()->where('status', ApprovalStatus::Waiting->value)->where('signer_type', 'external')->get()
            : collect([$next]);

        foreach ($toOpen as $a) {
            $a->forceFill(['status' => ApprovalStatus::Pending, 'opened_at' => now()])->save();
            DB::afterCommit(fn () => ApprovalRequested::dispatch($a));
        }

        return $ws;
    }

    /** Lock the workspace row for the rest of the surrounding transaction. */
    protected function lock(int|string $workspaceId): StudentDocumentWorkspace
    {
        return StudentDocumentWorkspace::query()
            ->whereKey($workspaceId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    protected function lockApproval(int $id): TransactionApproval
    {
        return TransactionApproval::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    /** Validate + apply a status change. Returns the previous status. */
    protected function transition(StudentDocumentWorkspace $ws, TransactionStatus $to, array $attributes = []): TransactionStatus
    {
        $from = $ws->workflow_status;

        if ($from === null) {
            throw WorkflowException::notWorkflowTransaction();
        }

        if (! $from->canTransitionTo($to)) {
            throw WorkflowException::invalidTransition($from, $to);
        }

        $ws->forceFill($attributes + ['workflow_status' => $to])->save();

        $this->notifyAfterTransition($ws->workspace_id, $from, $to);

        return $from;
    }
    /** Student: changes requested / rejected / completed. Reviewers: submitted or resubmitted. */
    protected function notifyAfterTransition(int $workspaceId, TransactionStatus $from, TransactionStatus $to): void
    {
        DB::afterCommit(function () use ($workspaceId, $from, $to) {
            try {
                $ws = StudentDocumentWorkspace::with('user')->find($workspaceId);
                if (! $ws) {
                    return;
                }

                if (in_array($to, [TransactionStatus::ActionRequired, TransactionStatus::Rejected, TransactionStatus::Completed, TransactionStatus::Voided], true)) {
                    $ws->user?->notify(WorkflowNotification::forStudent($ws, $to));
                }

                $resubmitted = $to === TransactionStatus::UnderReview && $from === TransactionStatus::ActionRequired;
                if ($to === TransactionStatus::Submitted || $resubmitted) {
                    $admins = User::whereHas('role', fn ($q) => $q->where('role_name', 'Admin'))->get();
                    Notification::send($admins, WorkflowNotification::forReviewers($ws, $resubmitted ? 'resubmitted' : 'submitted'));
                }
            } catch (\Throwable $e) {
                report($e); // a notification failure must never undo a state change
            }
        });
    }

    protected function skipOpenApprovals(StudentDocumentWorkspace $ws): void
    {
        $ws->approvals()
            ->whereIn('status', ApprovalStatus::openValues())
            ->update(['status' => ApprovalStatus::Skipped->value]);
    }

    /** VPSD-2026-00125. Gapless per year, safe under concurrency (row lock on the counter). */
    protected function nextTransactionCode(): string
    {
        $year = (int) now()->format('Y');

        DB::table('transaction_sequences')->insertOrIgnore(['year' => $year, 'last_number' => 0]);

        $current = DB::table('transaction_sequences')->where('year', $year)->lockForUpdate()->value('last_number');
        $next = ((int) $current) + 1;

        DB::table('transaction_sequences')->where('year', $year)->update(['last_number' => $next]);

        return sprintf('VPSD-%d-%05d', $year, $next);
    }

    protected function audit(
        StudentDocumentWorkspace $ws,
        ?User $actor,
        string $action,
        ?TransactionStatus $from = null,
        ?TransactionStatus $to = null,
        array $meta = [],
        ?int $approvalId = null,
    ): void {
        TransactionAuditLog::create([
            'workspace_id' => $ws->workspace_id,
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->role?->role_name,
            'action' => $action,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'approval_id' => $approvalId,
            'meta' => $meta ?: null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
    public function recordReportGenerated(StudentDocumentWorkspace $workspace, User $actor): void
    {
        DB::transaction(function () use ($workspace, $actor) {
            $this->lock($workspace->getKey());
            $this->audit($workspace, $actor, 'report.generated');
        });
    }
    /**
     * The Chairman emails (or re-emails) a member's secure link. He records how he verified the member first.
     * The link still needs the member's email + one-time code on arrival.
     */
    public function sendMemberLink(TransactionApproval $approval, User $sender, array $identityCheck): void
    {
        DB::transaction(function () use ($approval, $sender, $identityCheck) {
            $ws = $this->lock($approval->workspace_id);
            $approval = $this->lockApproval($approval->id);
            $this->assertMemberSender($ws, $approval, $sender);

            $method = $identityCheck['method'] ?? null;
            if (! array_key_exists($method, self::IDENTITY_METHODS) || empty($identityCheck['confirmed'])) {
                throw WorkflowException::identityCheckRequired();
            }

            $this->audit($ws, $sender, 'external.identity_checked', null, null, [
                'method' => $method,
                'note' => $identityCheck['note'] ?? null,
                'rater' => $approval->external_name,
                'rater_email' => ExternalSigning::mask((string) $approval->external_email),
            ], $approval->id);
        });

        // Outside the transaction: sending mail must not hold row locks. Throws if the mail fails.
        app(ExternalSigning::class)->invite($approval->fresh());
    }

    /** Fix a typo in a member's email before they have signed. The old link stops working. */
    public function correctMemberEmail(TransactionApproval $approval, User $sender, string $email): void
    {
        $email = Str::lower(trim($email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new WorkflowException('That email address is not valid.');
        }

        DB::transaction(function () use ($approval, $sender, $email) {
            $ws = $this->lock($approval->workspace_id);
            $approval = $this->lockApproval($approval->id);
            $this->assertMemberSender($ws, $approval, $sender);

            $old = (string) $approval->external_email;
            $approval->forceFill(['external_email' => $email])->save();

            SigningRequest::where('approval_id', $approval->id)->where('status', 'pending')->update(['status' => 'revoked']);

            $this->audit($ws, $sender, 'external.email_corrected', null, null, [
                'from' => ExternalSigning::mask($old),
                'to' => ExternalSigning::mask($email),
            ], $approval->id);
        });
    }

    protected function assertMemberSender(StudentDocumentWorkspace $ws, TransactionApproval $approval, User $sender): void
    {
        if (! DocumentRegistry::get($ws->document_type)->manualExternalInvites()
            || $ws->workflow_status !== TransactionStatus::UnderReview
            || $approval->signer_type !== 'external'
            || $approval->status !== ApprovalStatus::Pending) {
            throw WorkflowException::approvalNotActionable();
        }

        // Only the internal signer who already signed this document (the Chairman) may send the links.
        $isChairman = $ws->approvals()
            ->where('signer_type', 'internal')
            ->where('status', ApprovalStatus::Approved->value)
            ->where('acted_by', $sender->id)
            ->exists();

        if (! $isChairman) {
            throw WorkflowException::notSignatory();
        }
    }
    /** Who is reporting. Frozen into the record so the slip always shows the issuer, never a later account change. */
    protected function reporterSnapshot(User $issuer): array
    {
        $roles = $issuer->signatoryAssignments()->current()->with('role')->get()
            ->pluck('role.label')->filter()->unique()->implode(', ');

        $sig = null;
        if ($issuer->signature_path && Storage::disk(SignatureStore::DISK)->exists($issuer->signature_path)) {
            [$sig] = app(SignatureStore::class)->archive($issuer->signature_path);
        }

        return [
            'reporter_name' => FormSupport::systemValue($issuer, 'formatted_full_name'),
            'reporter_role' => $roles,
            'reporter_user_id' => $issuer->id,
            'reporter_signature_path' => $sig,
        ];
    }
}
