<?php

namespace App\Livewire\Student\Transactions;

use App\Enums\TransactionStatus;
use App\Exceptions\Workflow\WorkflowException;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\DocumentPdf;
use App\Services\Workflow\FormSupport;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\Documents\DocumentRegistry;
use App\Workflow\Documents\WorkflowDocument;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Services\Workflow\ExternalSignatureStore;
use App\Services\Workflow\SignatureStore;
use InvalidArgumentException;
use App\Services\Workflow\ItemPhotoStore;
use Livewire\WithFileUploads;

/**
 * One page for a student's digital transaction: fill in -> save draft -> submit -> track.
 *   student.transactions.new  /student/transactions/new/{type}
 *   student.transactions.show /student/transactions/{workspace}
 * The form comes from App\Workflow\Documents (code), not from the template editor.
 */
class Form extends Component
{
    public string $type = '';
    public ?int $workspaceId = null;
    use WithFileUploads;

    public $itemPhoto = null;

    /** @var array<string, mixed> the student's answers, keyed by field name */
    public array $values = [];
    public string $studentSignature = '';

    private const TIMELINE = [
        'transaction.created' => 'Draft created',
        'transaction.submitted' => 'Submitted to VPSD',
        'review.started' => 'VPSD started reviewing',
        'review.action_requested' => 'VPSD asked for changes',
        'student.responded' => 'You resubmitted',
        'review.accepted' => 'Sent for signing',
        'approval.approved' => 'Signed',
        'approvals.completed' => 'All signatures collected',
        'approval.rejected' => 'Rejected by signatory',
        'review.rejected' => 'Rejected by VPSD',
        'transaction.completed' => 'Completed',
        'document.generated' => 'Official PDF generated',
        'document.voided' => 'Voided by VPSD',
        'transaction.reissued' => 'Corrected draft created',
        'transaction.issued_by_office' => 'Issued by the VPSD office',
        'transaction.issued_by_role' => 'Issued by university staff',
        'schedule.set' => 'Counselling schedule set',
    ];

    public function mount(?string $type = null, ?int $workspace = null): void
    {
        $saved = [];

        if ($workspace) {
            $ws = StudentDocumentWorkspace::where('workspace_id', $workspace)->firstOrFail();
            abort_unless((int) $ws->user_id === (int) Auth::id(), 403);
            abort_unless($ws->usesWorkflow() && DocumentRegistry::has($ws->document_type), 404);

            $this->workspaceId = $ws->workspace_id;
            $this->type = $ws->document_type;
            $saved = $ws->field_values ?? [];
        } else {
            $doc = DocumentRegistry::get($type);
            abort_if($doc->issuedByOffice(), 404);
            $this->type = $doc->key();
        }

        foreach (array_keys($this->document->fields()) as $name) {
            $this->values[$name] = $saved[$name] ?? null;
        }
        foreach ($this->document->fields() as $name => $f) {
            $saved_value = $saved[$name] ?? null;
            $this->values[$name] = ($f['type'] ?? 'text') === 'checkbox' ? (bool) $saved_value : $saved_value;
        }
    }
    /** Saves a newly drawn signature, or keeps the one already on the record. */
    private function signaturePayload(?array $existing): array
    {
        if (! $this->document->requiresStudentSignature()) {
            return [];
        }

        if (trim($this->studentSignature) !== '') {
            try {
                [$path, $hash] = app(ExternalSignatureStore::class)->fromDataUrl($this->studentSignature);
            } catch (InvalidArgumentException $e) {
                throw new WorkflowException($e->getMessage());
            }

            return ['student_signature_path' => $path, 'student_signature_hash' => $hash];
        }

        return array_intersect_key($existing ?? [], array_flip(['student_signature_path', 'student_signature_hash']));
    }
    private function photoPayload(?array $existing): array
    {
        if (! $this->document->requiresItemPhoto()) {
            return [];
        }

        if ($this->itemPhoto) {
            try {
                [$path, $hash] = app(ItemPhotoStore::class)->store($this->itemPhoto);
            } catch (InvalidArgumentException $e) {
                throw new WorkflowException($e->getMessage());
            }

            return ['item_photo_path' => $path, 'item_photo_hash' => $hash];
        }

        return array_intersect_key($existing ?? [], array_flip(['item_photo_path', 'item_photo_hash']));
    }

    private function savedSignatureUri(?StudentDocumentWorkspace $ws): ?string
    {
        $path = $ws?->field_values['student_signature_path'] ?? null;
        $disk = Storage::disk(SignatureStore::DISK);

        if (! $path || ! str_starts_with($path, 'signatures/archive/') || ! $disk->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($disk->get($path));
    }

    #[Computed]
    public function document(): WorkflowDocument
    {
        return DocumentRegistry::get($this->type);
    }

    #[Computed]
    public function workspace(): ?StudentDocumentWorkspace
    {
        return $this->workspaceId
            ? StudentDocumentWorkspace::where('workspace_id', $this->workspaceId)->where('user_id', Auth::id())->first()
            : null;
    }

    #[Computed]
    public function isEditable(): bool
    {
        if ($this->document->issuedByOffice()) {
            return false;
        }

        $status = $this->workspace?->workflow_status;

        return $status === null || $status === TransactionStatus::Draft || $status === TransactionStatus::ActionRequired;
    }

    /* ------------------------------------------------------------------ */

    public function saveDraft()
    {
        $this->validate($this->rules(false), [], $this->attributes());

        try {
            $ws = DB::transaction(fn () => $this->persist());
        } catch (WorkflowException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        session()->flash('workflow_message', 'Draft saved.');

        if (! $this->workspaceId) {
            return $this->redirectRoute('student.transactions.show', ['workspace' => $ws->workspace_id], navigate: true);
        }

        unset($this->workspace, $this->isEditable);
    }

    public function submit()
    {
        $snapshot = $this->studentSnapshot();
        if ($snapshot['student_name'] === '' || $snapshot['student_program'] === '') {
            $this->addError('form', 'Complete your program, year level and section in your profile before submitting.');

            return;
        }

        $this->validate($this->rules(true), [], $this->attributes());

        if ($message = $this->document->check($this->values)) {
            $this->addError('form', $message);

            return;
        }
        if ($this->document->requiresStudentSignature()
            && trim($this->studentSignature) === ''
            && empty($this->workspace?->field_values['student_signature_path'])) {
            $this->addError('studentSignature', 'Please draw your signature in the box.');

            return;
        }
        if ($this->document->requiresItemPhoto()
            && ! $this->itemPhoto
            && empty($this->workspace?->field_values['item_photo_path'])) {
            $this->addError('itemPhoto', 'Please take or upload a photo of the item.');

            return;
        }
        try {
            $ws = DB::transaction(function () {
                $ws = $this->persist();
                $workflow = app(TransactionWorkflow::class);

                return $ws->workflow_status === TransactionStatus::ActionRequired
                    ? $workflow->respondToActionRequest($ws, Auth::user())
                    : $workflow->submit($ws, Auth::user());
            });
        } catch (WorkflowException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        session()->flash('workflow_message', 'Submitted to the VPSD office. You will see each step here.');

        return $this->redirectRoute('student.transactions.show', ['workspace' => $ws->workspace_id], navigate: true);
    }

    /** Student saves their copy: only after COMPLETED. Regenerates the PDF if it is missing. */
    public function downloadPdf()
    {
        $ws = $this->workspace;
        abort_unless($ws && $ws->workflow_status === TransactionStatus::Completed, 403);

        $path = $ws->generated_pdf_path;
        if (! $path || ! Storage::disk(DocumentPdf::DISK)->exists($path)) {
            $path = app(DocumentPdf::class)->generateFor($ws);
        }

        app(TransactionWorkflow::class)->recordDownload($ws, Auth::user());

        return Storage::disk(DocumentPdf::DISK)->download($path, $ws->transaction_code . '-' . $this->document->key() . '.pdf');
    }

    /* ------------------------------------------------------------------ */

    /** Account details printed on the document. Frozen into the record on every save. */
    private function studentSnapshot(): array
    {
        $user = Auth::user();

        return [
            'student_name' => FormSupport::systemValue($user, 'formatted_full_name'),
            'student_program' => FormSupport::systemValue($user, 'program_year_section'),
            'student_course' => FormSupport::systemValue($user, 'program'),
            'student_number' => FormSupport::systemValue($user, 'student_number'),
            'officer_position' => FormSupport::systemValue($user, 'officer_position'),
            'officer_organization' => FormSupport::systemValue($user, 'officer_organization'),
        ];
    }

    private function persist(): StudentDocumentWorkspace
    {
        $answers = [];
        foreach ($this->document->fields() as $name => $f) {
            $value = $this->values[$name] ?? null;

            if (($f['type'] ?? 'text') === 'checkbox') {
                $answers[$name] = (bool) $value;
                continue;
            }

            $value = is_string($value) ? trim($value) : $value;
            $answers[$name] = $value === '' ? null : $value;
        }
        $payload = $answers + $this->studentSnapshot() + $this->signaturePayload(null) + $this->photoPayload(null);

        if (! $this->workspaceId) {
            $ws = new StudentDocumentWorkspace();
            $ws->forceFill([
                'user_id' => Auth::id(),
                'document_type' => $this->type,
                'field_values' => $payload,
                'status' => 'pending', // legacy column; the workflow state lives in workflow_status
            ])->save();

            return app(TransactionWorkflow::class)->initialize($ws);
        }

        $ws = StudentDocumentWorkspace::where('workspace_id', $this->workspaceId)
            ->where('user_id', Auth::id())
            ->lockForUpdate()
            ->firstOrFail();

        if (! in_array($ws->workflow_status, [TransactionStatus::Draft, TransactionStatus::ActionRequired], true)) {
            throw new WorkflowException('This transaction can no longer be edited.');
        }

        $payload = $answers + $this->studentSnapshot() + $this->signaturePayload($ws->field_values) + $this->photoPayload($ws->field_values);
        $ws->update(['field_values' => $payload]);

        return $ws->fresh();
    }
    public function updatedItemPhoto(): void
    {
        try {
            $this->validateOnly('itemPhoto', ['itemPhoto' => ['image', 'mimes:jpg,jpeg,png', 'max:5120']]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->reset('itemPhoto');
            throw $e;
        }
    }

    private function rules(bool $strict): array
    {
        $rules = [];

        foreach ($this->document->fields() as $name => $f) {
            if (($f['type'] ?? 'text') === 'checkbox') {
                $rules["values.$name"] = ['nullable', 'boolean'];
                continue;
            }
            $r = [$strict && ($f['required'] ?? false) ? 'required' : 'nullable'];
            $r[] = ($f['type'] ?? 'text') === 'date' ? 'date' : 'string';
            if (isset($f['max'])) {
                $r[] = 'max:' . $f['max'];
            }
            $rules["values.$name"] = array_merge($r, $f['rules'] ?? []);
        }
        $rules['studentSignature'] = ['nullable', 'string', 'max:400000'];
        $rules['itemPhoto'] = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];
        return $rules;
    }

    private function attributes(): array
    {
        $attrs = [];
        foreach ($this->document->fields() as $name => $f) {
            $attrs["values.$name"] = strtolower($f['label']);
        }

        return $attrs;
    }

    private function timeline(StudentDocumentWorkspace $ws): array
    {
        return $ws->auditLogs()->get()
            ->filter(fn ($log) => isset(self::TIMELINE[$log->action]))
            ->map(function ($log) {
                $meta = $log->meta ?? [];
                $text = self::TIMELINE[$log->action];
                if (! empty($meta['label']) && str_starts_with($log->action, 'approval.')) {
                    $text .= ' — ' . $meta['label'];
                }

                return ['text' => $text, 'note' => $meta['message'] ?? $meta['reason'] ?? null, 'at' => $log->created_at];
            })
            ->reverse()->values()->all();
    }

    public function render()
    {
        $ws = $this->workspace;
        $approvals = $ws ? $ws->approvals()->with('role')->get() : collect();
        $status = $ws?->workflow_status;

        // Which of the four progress steps is current (4 = all done).
        $progress = match (true) {
            $status === TransactionStatus::Completed => 4,
            $status === TransactionStatus::Approved => 3,
            $status === TransactionStatus::UnderReview && $approvals->isNotEmpty() => 2,
            $status !== null && $status !== TransactionStatus::Draft && $status !== TransactionStatus::Rejected => 1,
            $status !== null && $status !== TransactionStatus::Draft && $status !== TransactionStatus::Rejected && $status !== TransactionStatus::Voided => 1,
            default => null,
        };

        return view('livewire.student.transactions.form', [
            'ws' => $ws,
            'status' => $status,
            'approvals' => $approvals,
            'progress' => $progress,
            'timeline' => $ws ? $this->timeline($ws) : [],
            // Editable: live account details. Otherwise: what was frozen into the record.
            'student' => $this->isEditable || ! $ws ? $this->studentSnapshot() : [
                'student_name' => $ws->field_values['student_name'] ?? '',
                'student_program' => $ws->field_values['student_program'] ?? '',
                'officer_position' => $ws->field_values['officer_position'] ?? '',
                'officer_organization' => $ws->field_values['officer_organization'] ?? '',
                'afterReviewNote' => $ws && in_array($status, [
                    TransactionStatus::UnderReview,
                    TransactionStatus::Approved,
                    TransactionStatus::Completed,
                ], true)
                    ? $this->document->afterReviewNote($ws->field_values ?? [])
                    : null,
            ],
            'savedSignature' => $this->savedSignatureUri($ws),
            'hasPhoto' => ! empty($ws?->field_values['item_photo_path']),
            
        ])->layout('layouts.app', ['title' => $this->document->name()]);
    }
}