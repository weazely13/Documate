<?php

namespace App\Livewire\Admin\Workflow;

use App\Exceptions\Workflow\WorkflowException;
use App\Models\User;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\Documents\DocumentRegistry;
use App\Workflow\Documents\WorkflowDocument;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Enums\ApprovalStatus;

/** VPSD issues a document TO a student (e.g. the Student Appointment Slip). One button: it goes straight to signing. */
class Issue extends Component
{
    public string $type = '';
    public string $studentSearch = '';
    public ?int $studentId = null;

    /** @var array<string, mixed> */
    public array $values = [];

    public function mount(): void
    {
        $docs = $this->officeDocuments();
        abort_if(empty($docs), 404);

        $this->type = (string) array_key_first($docs);
        $this->resetValues();
    }

    /** @return array<string, WorkflowDocument> */
    private function officeDocuments(): array
    {
        $user = Auth::user();

        return array_filter(
            DocumentRegistry::all(),
            fn (WorkflowDocument $d) => $d->issuedByOffice() && $d->canBeIssuedBy($user)
        );
    }

    private function resetValues(): void
    {
        $this->values = [];
        foreach ($this->document->fields() as $name => $f) {
            $this->values[$name] = ($f['type'] ?? 'text') === 'checkbox' ? false : null;
        }
    }

    public function updatedType(): void
    {
        if (! array_key_exists($this->type, $this->officeDocuments())) {
            $this->type = (string) array_key_first($this->officeDocuments());
        }
        unset($this->document);
        $this->resetValues();
        $this->resetErrorBag();
    }

    public function updatingStudentSearch(): void
    {
        $this->studentId = null;
    }

    public function selectStudent(int $userId): void
    {
        $this->studentId = $userId;
        $this->studentSearch = '';
    }

    #[Computed]
    public function document(): WorkflowDocument
    {
        return $this->officeDocuments()[$this->type];
    }

    #[Computed]
    public function selectedStudent(): ?User
    {
        return $this->studentId ? User::with('program')->find($this->studentId) : null;
    }

    #[Computed]
    public function studentResults()
    {
        if (strlen($this->studentSearch) < 2) {
            return collect();
        }

        $term = '%' . $this->studentSearch . '%';

        return User::query()
            ->with('program')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Student', 'Officer']))
            ->where('account_status', 'active')
            ->where(fn ($q) => $q
                ->where('first_name', 'like', $term)
                ->orWhere('last_name', 'like', $term)
                ->orWhere('student_number', 'like', $term))
            ->orderBy('last_name')
            ->limit(8)
            ->get();
    }

    private function rules(): array
    {
        $rules = [
            'studentId' => ['required', 'exists:users,id'],
            'type' => ['required', 'in:' . implode(',', array_keys($this->officeDocuments()))],
        ];

        foreach ($this->document->fields() as $name => $f) {
            $kind = $f['type'] ?? 'text';

            if ($kind === 'checkbox') {
                $rules["values.$name"] = ['nullable', 'boolean'];
                continue;
            }

            $r = [($f['required'] ?? false) ? 'required' : 'nullable'];
            $r[] = match ($kind) {
                'date' => 'date',
                'time' => 'date_format:H:i',
                default => 'string',
            };
            if (isset($f['max'])) {
                $r[] = 'max:' . $f['max'];
            }

            $rules["values.$name"] = array_merge($r, $f['rules'] ?? []);
        }

        return $rules;
    }

    private function attributes(): array
    {
        $attrs = ['studentId' => 'student'];
        foreach ($this->document->fields() as $name => $f) {
            $attrs["values.$name"] = strtolower($f['label']);
        }

        return $attrs;
    }

    public function issue()
    {
        $this->resetErrorBag();
        $this->validate($this->rules(), [], $this->attributes());

        $student = User::findOrFail($this->studentId);

        try {
            $ws = app(TransactionWorkflow::class)->issueForStudent(Auth::user(), $student, $this->type, $this->values);
        } catch (WorkflowException $e) {
            $this->addError('form', $e->getMessage());

            return;
        }

        if ($this->document->issuerIsStaff()) {
            $manual = $this->document->manualExternalInvites();
            $mine = $ws->approvals()->where('status', ApprovalStatus::Pending->value)->first();
            $mine = $mine && $mine->isAssignedTo(Auth::user()) ? $mine : null;

            $msg = 'Issued. The student was notified.';
            if ($mine) {
                $msg .= $manual ? ' Sign it now, then send it to the committee members.' : ' It is waiting in your signing inbox.';
            }
            session()->flash('signing_message', $msg);

            return $mine && $manual
                ? $this->redirectRoute('signatory.show', ['approval' => $mine->id], navigate: true)
                : $this->redirectRoute('signatory.inbox', navigate: true);
        }

        return $this->redirectRoute('admin.workflow.show', ['workspace' => $ws->workspace_id], navigate: true);
    }
    

    public function render()
    {
        return view('livewire.admin.workflow.issue', [
            'documents' => $this->officeDocuments(),
        ])->layout('layouts.app', ['title' => 'Issue Document']);
    }
}