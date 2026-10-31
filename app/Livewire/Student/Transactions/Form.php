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
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;
use App\Services\Workflow\ExternalSignatureStore;
use App\Services\Workflow\SignatureStore;
use InvalidArgumentException;
use App\Services\Workflow\ItemPhotoStore;
use Livewire\WithFileUploads;
use App\Models\User;

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
    public string $reportDate = '';
    public string $reportSession = '';
    public ?int $nomineeId = null;
    public string $nomineeSearch = '';

    /** Search box for a "students" field (e.g. the interviewees). */
    public string $studentSearch = '';

    /** The student confirms the answers are true and agrees to how their data is used. Asked on every submit. */
    public bool $consent = false;

    /** Where the back link goes: "new" when opened from the New transaction picker. */
    #[Url(except: '')]
    public string $from = '';

    public const TIMELINE = [
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
            // The owner, or a student named on it (read-only copy).
            abort_unless($ws->isVisibleTo((int) Auth::id()), 403);
            abort_unless($ws->usesWorkflow() && DocumentRegistry::has($ws->document_type), 404);

            $this->workspaceId = $ws->workspace_id;
            $this->type = $ws->document_type;
            $saved = $ws->field_values ?? [];
            $this->nomineeId = ! empty($saved['nominee_user_id']) ? (int) $saved['nominee_user_id'] : null;
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

            if (($f['type'] ?? 'text') === 'slots') {
                $this->values[$name] = $this->initialSlots($saved, $name);
                continue;
            }
            if (($f['type'] ?? 'text') === 'students') {
                $this->values[$name] = array_values(is_array($saved_value) ? $saved_value : []);
                continue;
            }

            $this->values[$name] = ($f['type'] ?? 'text') === 'checkbox' ? (bool) $saved_value : $saved_value;
        }
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
        if (! $this->workspaceId) {
            return null;
        }

        $ws = StudentDocumentWorkspace::where('workspace_id', $this->workspaceId)->first();

        return $ws && $ws->isVisibleTo((int) Auth::id()) ? $ws : null;
    }

    /** False for a student who was only named on someone else's transaction. */
    #[Computed]
    public function isOwner(): bool
    {
        return ! $this->workspace || (int) $this->workspace->user_id === (int) Auth::id();
    }

    #[Computed]
    public function isEditable(): bool
    {
        if ($this->document->issuedByOffice() || ! $this->isOwner) {
            return false;
        }

        $status = $this->workspace?->workflow_status;

        return $status === null || $status === TransactionStatus::Draft || $status === TransactionStatus::ActionRequired;
    }

    // new helpers
    private static function blankSlot(): array
    {
        return ['date' => '', 'from' => '', 'to' => ''];
    }

    private function initialSlots(array $saved, string $name): array
    {
        if (! empty($saved[$name]) && is_array($saved[$name])) {
            return array_map(fn ($s) => [
                'date' => (string) ($s['date'] ?? ''),
                'from' => substr((string) ($s['from'] ?? ''), 0, 5),
                'to' => substr((string) ($s['to'] ?? ''), 0, 5),
            ], array_values($saved[$name]));
        }
        if (! empty($saved['activity_date'])) {   // older drafts
            return [['date' => $saved['activity_date'], 'from' => substr((string) ($saved['activity_time'] ?? ''), 0, 5), 'to' => '']];
        }

        return [self::blankSlot()];
    }

    private function isSlotsField(string $name): bool
    {
        return (($this->document->fields()[$name]['type'] ?? '') === 'slots');
    }

    private function pruneSlots(): void
    {
        foreach ($this->document->fields() as $name => $f) {
            if (($f['type'] ?? '') !== 'slots') {
                continue;
            }
            $rows = array_values(array_filter($this->values[$name] ?? [], fn ($r) =>
                trim((string) ($r['date'] ?? '')) !== '' || trim((string) ($r['from'] ?? '')) !== '' || trim((string) ($r['to'] ?? '')) !== ''));
            $this->values[$name] = $rows ?: [self::blankSlot()];
        }
    }

    public function addSlot(string $name): void
    {
        if (! $this->isEditable || ! $this->isSlotsField($name)) {
            return;
        }
        if (count($this->values[$name] ?? []) < ($this->document->fields()[$name]['max_slots'] ?? 10)) {
            $this->values[$name][] = self::blankSlot();
        }
    }

    public function removeSlot(string $name, int $index): void
    {
        if (! $this->isEditable || ! $this->isSlotsField($name)) {
            return;
        }
        unset($this->values[$name][$index]);
        $this->values[$name] = array_values($this->values[$name]) ?: [self::blankSlot()];
    }

    /* ------------------------------------------------------------------ */

    public function saveDraft()
    {
        $this->pruneSlots();
        $this->validate($this->rules(false), [], $this->attributes());

        try {
            $ws = DB::transaction(fn () => $this->persist());
        } catch (WorkflowException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        session()->flash('workflow_message', 'Draft saved.');

        if (! $this->workspaceId) {
            return $this->redirectRoute('student.transactions.show', $this->showParams($ws), navigate: true);
        }

        unset($this->workspace, $this->isEditable);
    }

    public function submit()
    {
        $this->pruneSlots();
        $snapshot = $this->studentSnapshot();

        $missing = collect($this->document->systemFields())
            ->filter(fn ($label, $key) => trim((string) ($snapshot[$key] ?? '')) === '')
            ->values();

        $this->validate(
            $this->rules(true) + ['consent' => ['accepted']],
            ['consent.accepted' => 'Tick the box to confirm your answers and give your consent before submitting.'],
            $this->attributes(),
        );

        if ($message = $this->document->check($this->values)) {
            $this->addError('form', $message);

            return;
        }
        if ($this->document->usesNomineePicker() && ! $this->nominee) {
            $this->addError('nomineeId', 'Search for the nominee and select them from the list.');

            return;
        }
        if ($this->document->usesNomineePicker() && $this->nominee) {
            $snap = $this->document->nomineeSnapshot($this->nominee);

            if (trim((string) ($snap['nominee_address'] ?? '')) === '') {
                $this->addError('nomineeId', 'This nominee has no address on their profile. Ask them to add it before you nominate them.');

                return;
            }
            if (trim((string) ($snap['nominee_contact'] ?? '')) === '') {
                $this->addError('nomineeId', 'This nominee has no contact number on their profile. Ask them to add it before you nominate them.');

                return;
            }
        }

        if ($missing->isNotEmpty()) {
            $this->addError('form', 'Complete these in your profile before submitting: ' . $missing->implode(', ') . '.');

            return;
        }
        if ($this->document->requiresStudentSignature()
            && ! $this->profileSignatureReady()
            && empty($this->workspace?->field_values['student_signature_path'])) {
            $this->addError('form', 'Save your e-signature in your profile before submitting.');

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
                $this->syncParticipants($ws);
                $workflow = app(TransactionWorkflow::class);

                return $ws->workflow_status === TransactionStatus::ActionRequired
                    ? $workflow->respondToActionRequest($ws, Auth::user())
                    : $workflow->submit($ws, Auth::user());
            });
        } catch (WorkflowException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        $this->consent = false;
        session()->flash('workflow_message', 'Submitted to the VPSD office. You will see each step here.');

        return $this->redirectRoute('student.transactions.show', $this->showParams($ws), navigate: true);
    }

    /** Route parameters for the saved transaction, keeping where the student came from. */
    private function showParams(StudentDocumentWorkspace $ws): array
    {
        return array_filter(['workspace' => $ws->workspace_id, 'from' => $this->from === 'new' ? 'new' : null]);
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

        if ($this->isOwner) {
            app(TransactionWorkflow::class)->recordDownload($ws, Auth::user());
        }
        $this->dispatch('toast', type: 'success', title: 'Download complete',
            message: 'Your PDF has been downloaded. Print it only if a physical copy is required.');

        return Storage::disk(DocumentPdf::DISK)->download($path, $ws->transaction_code . '-' . $this->document->key() . '.pdf');
    }

    /* ------------------------------------------------------------------ */

    /** Account details printed on the document. Frozen into the record on every save. */
    private function studentSnapshot(): array
    {
        return FormSupport::studentSnapshot(Auth::user(), $this->document);
    }

    /** The form's answers, cleaned the way they are stored. Writes nothing. */
    private function answers(): array
    {
        $answers = [];
        foreach ($this->document->fields() as $name => $f) {
            $value = $this->values[$name] ?? null;

            if (($f['type'] ?? 'text') === 'checkbox') {
                $answers[$name] = (bool) $value;
                continue;
            }

            $value = is_string($value) ? trim($value) : $value;

            if (($f['type'] ?? 'text') === 'students') {
                $answers[$name] = $this->resolveStudents(is_array($value) ? $value : [], $f['max_items'] ?? 10) ?: null;
                continue;
            }

            if (($f['type'] ?? 'text') === 'slots') {
                $rows = collect(is_array($value) ? $value : [])
                    ->map(fn ($r) => [
                        'date' => trim((string) ($r['date'] ?? '')),
                        'from' => substr(trim((string) ($r['from'] ?? '')), 0, 5),
                        'to' => substr(trim((string) ($r['to'] ?? '')), 0, 5) ?: null,
                    ])
                    ->filter(fn ($r) => $r['date'] !== '' || $r['from'] !== '')
                    ->sortBy(fn ($r) => $r['date'] . ' ' . $r['from'])
                    ->values()->all();
                $answers[$name] = $rows ?: null;
                continue;
            }
            $answers[$name] = $value === '' ? null : $value;
        }

        // Documents built on another of the student's records (e.g. the endorsement copies the approved application's schedule).
        if ($this->document->sourceDocumentType() && method_exists($this->document, 'sourceFor')) {
            $source = $this->document->sourceFor(Auth::user(), $answers['source_application'] ?? null);
            if ($source) {
                $answers = $this->document->fillFromSource($answers, $source);
            }
        }

        return $answers;
    }

    /**
     * The document as it will print, for the student's preview. While the form is still editable it shows
     * the live answers without saving them; signatures stay blank until submission, and the QR code and
     * fingerprint stay placeholders until the document is issued.
     */
    #[Renderless]
    public function previewDocument(): string
    {
        $ws = $this->workspace;

        if ($ws && ! $this->isEditable) {
            return app(DocumentPdf::class)->previewHtml($ws->loadMissing('user'));
        }

        $live = $this->answers() + $this->studentSnapshot() + $this->nomineePayload($ws?->field_values);

        if ($ws) {
            $draft = clone $ws; // never saved: approvals still load for an action-required transaction
            $draft->field_values = $live + array_intersect_key($ws->field_values ?? [], array_flip(['item_photo_path', 'item_photo_hash']));
        } else {
            $draft = (new StudentDocumentWorkspace())->forceFill([
                'user_id' => Auth::id(),
                'document_type' => $this->type,
                'field_values' => $live,
            ]);
        }

        return app(DocumentPdf::class)->previewHtml($draft->setRelation('user', Auth::user()));
    }

    private function persist(): StudentDocumentWorkspace
    {
        $answers = $this->answers();
        $payload = $answers + $this->studentSnapshot() + $this->signaturePayload(null) + $this->photoPayload(null) + $this->nomineePayload(null);

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

        $payload = $answers + $this->studentSnapshot() + $this->signaturePayload($ws->field_values) + $this->photoPayload($ws->field_values) + $this->nomineePayload($ws->field_values);
        $ws->update(['field_values' => $payload]);

        return $ws->fresh();
    }
    public function updatedItemPhoto(): void
    {
        try {
            $this->validateOnly('itemPhoto', ['itemPhoto' => ['image', 'mimes:jpg,jpeg,jfif,png', 'max:10240']]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->reset('itemPhoto');
            throw $e;
        }
    }

    private function rules(bool $strict): array
    {
        $rules = [];

        foreach ($this->document->fields() as $name => $f) {
            $type = $f['type'] ?? 'text';

            if ($type === 'checkbox') {
                $rules["values.$name"] = ['nullable', 'boolean'];
                continue;
            }
            if ($type === 'students') {
                $req = $strict && ($f['required'] ?? false);
                $rules["values.$name"] = [$req ? 'required' : 'nullable', 'array', 'max:' . ($f['max_items'] ?? 10)];
                $rules["values.$name.*.id"] = ['required', 'integer', 'distinct'];
                continue;
            }
            if ($type === 'slots') {
                $req = $strict && ($f['required'] ?? false);
                $rules["values.$name"] = [$req ? 'required' : 'nullable', 'array', 'max:' . ($f['max_slots'] ?? 10)];
                $rules["values.$name.*.date"] = [$strict ? 'required' : 'nullable', 'date', 'after_or_equal:today'];
                $rules["values.$name.*.from"] = [$strict ? 'required' : 'nullable', 'date_format:H:i,H:i:s'];
                $rules["values.$name.*.to"] = ['nullable', 'date_format:H:i,H:i:s'];
                continue;
            }

            $r = [$strict && ($f['required'] ?? false) ? 'required' : 'nullable'];
            if ($type === 'date' && ! empty($f['past_only'])) {
                $r[] = 'before:today';
            }
            $r[] = match ($type) {
                'date' => 'date',
                'time' => 'date_format:H:i',
                default => 'string',
            };
            if (isset($f['max'])) {
                $r[] = 'max:' . $f['max'];
            }

            $rules["values.$name"] = array_merge($r, $f['rules'] ?? []);
        }
        $rules['itemPhoto'] = ['nullable', 'image', 'mimes:jpg,jpeg,jfif,png', 'max:10240'];
        return $rules;
    }

    private function attributes(): array
    {
        $attrs = [];
        foreach ($this->document->fields() as $name => $f) {
            $attrs["values.$name"] = strtolower($f['label']);

            if (($f['type'] ?? '') === 'slots') {
                $attrs["values.$name.*.date"] = 'date';
                $attrs["values.$name.*.from"] = 'start time';
                $attrs["values.$name.*.to"] = 'end time';
            }
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
    private function profileSignatureReady(): bool
    {
        $u = Auth::user();
        return $u->signature_enabled && $u->signature_path
            && Storage::disk(SignatureStore::DISK)->exists($u->signature_path);
    }

    private function signaturePayload(?array $existing): array
    {
        if (! $this->document->requiresStudentSignature()) {
            return [];
        }

        if ($this->profileSignatureReady()) {
            [$path, $hash] = app(SignatureStore::class)->archive(Auth::user()->signature_path);

            return ['student_signature_path' => $path, 'student_signature_hash' => $hash];
        }

        if (! empty($existing['student_signature_path'])) {
            return array_intersect_key($existing, array_flip(['student_signature_path', 'student_signature_hash']));
        }

        return [];
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

        $nomineeCard = null;
        if ($this->document->usesNomineePicker()) {
            $src = ($this->isEditable && $this->nominee)
                ? $this->document->nomineeSnapshot($this->nominee)
                : ($ws?->field_values ?? []);

            if (! empty($src['nominee_name'])) {
                $nomineeCard = [
                    'name' => $src['nominee_name'],
                    'college' => $src['nominee_college'] ?? '',
                    'program' => $src['nominee_program'] ?? '',
                    'year_section' => $src['nominee_year_section'] ?? '',
                ];
            }
        }

        // Opened from the picker (or not saved yet): back to New transaction. Otherwise My documents.
        $backToPicker = ($this->from === 'new' || ! $ws) && $this->isOwner;

        return view('livewire.student.transactions.form', [
            'back' => $backToPicker
                ? ['url' => route('student.home'), 'label' => 'Home']
                : ['url' => route('student.documents.index'), 'label' => 'My documents'],
            'ws' => $ws,
            'status' => $status,
            'approvals' => $approvals,
            'progress' => $progress,
            'timeline' => $ws ? $this->timeline($ws) : [],
            'student' => $this->isEditable || ! $ws ? $this->studentSnapshot() : [
                'student_name' => $ws->field_values['student_name'] ?? '',
                'student_program' => $ws->field_values['student_program'] ?? '',
                'student_number' => $ws->field_values['student_number'] ?? '',
                'contact_number' => $ws->field_values['contact_number'] ?? '',
                'officer_position' => $ws->field_values['officer_position'] ?? '',
                'officer_organization' => $ws->field_values['officer_organization'] ?? '',
            ],
            'nomineeCard' => $nomineeCard,
            'afterReviewNote' => $ws && in_array($status, [
                TransactionStatus::UnderReview,
                TransactionStatus::Approved,
                TransactionStatus::Completed,
            ], true)
                ? $this->document->afterReviewNote($ws->field_values ?? [])
                : null,
            'savedSignature' => $this->savedSignatureUri($ws),
            'hasPhoto' => ! empty($ws?->field_values['item_photo_path']),
            'profileSignature' => $this->profileSignatureReady(),
            'isOwner' => $this->isOwner,
            'owner' => $ws && ! $this->isOwner ? ($ws->field_values['student_name'] ?? 'another student') : null,
            'officerLine' => $this->document->requiresOfficer() ? $this->officerLine() : null,
        ])->layout('layouts.app', ['title' => $this->document->name()]);
    }
    private function eligibleNominees()
    {
        return User::query()
            ->with('program')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Student', 'Officer']))
            ->where('account_status', 'active')
            ->where('id', '!=', Auth::id());
    }

    #[Computed]
    public function nominee(): ?User
    {
        return $this->nomineeId ? $this->eligibleNominees()->whereKey($this->nomineeId)->first() : null;
    }

    #[Computed]
    public function nomineeResults()
    {
        if (! $this->document->usesNomineePicker() || strlen($this->nomineeSearch) < 2) {
            return collect();
        }

        $term = '%' . $this->nomineeSearch . '%';

        return $this->eligibleNominees()
            ->where(fn ($q) => $q
                ->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('student_number', 'like', $term))
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    public function selectNominee(int $userId): void
    {
        if (! $this->isEditable || ! $this->document->usesNomineePicker()) {
            return;
        }

        if (! $this->eligibleNominees()->whereKey($userId)->exists()) {
            $this->addError('nomineeId', 'That student cannot be nominated.');

            return;
        }

        $this->nomineeId = $userId;
        $this->nomineeSearch = '';
        $this->resetErrorBag('nomineeId');
        unset($this->nominee);
    }

    public function clearNominee(): void
    {
        if ($this->isEditable) {
            $this->nomineeId = null;
            unset($this->nominee);
        }
    }

    /** Nominee details copied from the account. Keeps the saved ones if the account is no longer eligible. */
    private function nomineePayload(?array $existing): array
    {
        if (! $this->document->usesNomineePicker()) {
            return [];
        }

        if ($n = $this->nominee) {
            return $this->document->nomineeSnapshot($n);
        }

        return array_filter(
            $existing ?? [],
            fn ($key) => str_starts_with($key, 'nominee_'),
            ARRAY_FILTER_USE_KEY
        );
    }

    /* ---------------------------- student list ---------------------------- */

    /** Students who can be listed on a "students" field: active student accounts (the owner may list themselves). */
    private function listableStudents()
    {
        return User::query()
            ->with('program')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Student', 'Officer']))
            ->where('account_status', 'active');
    }

    private function studentsField(): ?array
    {
        foreach ($this->document->fields() as $name => $f) {
            if (($f['type'] ?? '') === 'students') {
                return ['name' => $name] + $f;
            }
        }

        return null;
    }

    #[Computed]
    public function studentResults()
    {
        $field = $this->studentsField();
        $term = trim($this->studentSearch);
        if (! $field || mb_strlen($term) < 2) {
            return collect();
        }

        $picked = array_map('intval', array_column($this->values[$field['name']] ?? [], 'id'));
        $like = '%' . $term . '%';
        [$first, $last] = array_pad(preg_split('/\s+/', $term, 2), 2, null);

        return $this->listableStudents()
            ->whereNotIn('id', $picked ?: [0])
            ->where(fn ($q) => $q
                ->where('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('student_number', 'like', $like)
                ->when($last, fn ($q) => $q->orWhere(fn ($n) => $n
                    ->where('first_name', 'like', '%' . $first . '%')
                    ->where('last_name', 'like', '%' . $last . '%'))))
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    public function addStudent(int $userId): void
    {
        $field = $this->studentsField();
        if (! $field || ! $this->isEditable) {
            return;
        }

        $name = $field['name'];
        $list = array_values($this->values[$name] ?? []);
        $max = $field['max_items'] ?? 10;

        if (count($list) >= $max) {
            $this->addError("values.$name", "You can list up to {$max} students.");

            return;
        }
        if (in_array($userId, array_map('intval', array_column($list, 'id')), true)) {
            return;
        }

        $user = $this->listableStudents()->whereKey($userId)->first();
        if (! $user) {
            $this->addError("values.$name", 'That student has no active DocuMate account.');

            return;
        }

        $list[] = $this->studentRow($user);
        $this->values[$name] = $list;
        $this->studentSearch = '';
        $this->resetErrorBag("values.$name");
        unset($this->studentResults);
    }

    public function removeStudent(int $index): void
    {
        $field = $this->studentsField();
        if (! $field || ! $this->isEditable) {
            return;
        }

        $list = array_values($this->values[$field['name']] ?? []);
        unset($list[$index]);
        $this->values[$field['name']] = array_values($list);
    }

    private function studentRow(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => FormSupport::systemValue($user, 'formatted_full_name'),
            'number' => (string) ($user->student_number ?? ''),
            'program' => FormSupport::systemValue($user, 'program_year_section'),
        ];
    }

    /** Re-reads the listed students from their accounts, so nothing sent from the browser is trusted. */
    private function resolveStudents(array $rows, int $max): array
    {
        $ids = array_slice(array_values(array_unique(array_filter(array_map(fn ($r) => (int) (is_array($r) ? ($r['id'] ?? 0) : 0), $rows)))), 0, $max);
        if ($ids === []) {
            return [];
        }

        $users = $this->listableStudents()->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->filter(fn ($id) => $users->has($id))
            ->map(fn ($id) => $this->studentRow($users[$id]))
            ->values()->all();
    }

    /** Named students get a read-only copy. The owner is never their own participant. */
    private function syncParticipants(StudentDocumentWorkspace $ws): void
    {
        $sync = [];
        foreach ($this->document->participants($ws->field_values ?? []) as $userId => $role) {
            if ((int) $userId !== (int) $ws->user_id) {
                $sync[(int) $userId] = ['role' => $role];
            }
        }

        $ws->participants()->sync($sync);
    }

    /** "President, Computer Society" from the student's officer record, or null when they have none. */
    private function officerLine(): ?string
    {
        $snap = $this->isEditable ? $this->studentSnapshot() : ($this->workspace?->field_values ?? []);
        $line = collect([$snap['officer_position'] ?? null, $snap['officer_organization'] ?? null])->filter()->implode(', ');

        return $line !== '' ? $line : null;
    }
}