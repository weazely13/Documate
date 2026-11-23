<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\StudentDocumentWorkspace;
use App\Models\Template;
use App\Models\TransactionApproval;
use App\Models\User;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The top bar's search, second half: documents and appointments that match what was typed (a name, a transaction
 * code, a student, a purpose, a date), limited to what this person may open. The pages half is in the browser.
 */
class RecordSearchController extends Controller
{
    private const LIMIT = 6;

    public function __invoke(Request $request): JsonResponse
    {
        $q = trim(Str::limit((string) $request->query('q', ''), 80, ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['items' => []]);
        }

        $user = $request->user();
        $types = $this->documentTypesNamed($q);
        $templates = Template::query()->where('name', 'like', "%{$q}%")->pluck('template_id')->all();
        $date = $this->dateIn($q);

        $items = collect();
        if ($user->isAdmin()) {
            $items = $items->merge($this->documents(StudentDocumentWorkspace::query(), $q, $types, $templates, $date, true, fn ($w) => $w->usesWorkflow()
                ? route('admin.transactions.online', $w->workspace_id, false)
                : route('admin.transactions.show', $w->workspace_id, false)));
            $items = $items->merge($this->appointments(Appointment::query(), $q, $date, true, fn ($a) => route('admin.appointments.show', $a->appointment_id, false)));
        } else {
            if (in_array(strtolower((string) $user->role?->role_name), ['student', 'officer'], true)) {
                $items = $items->merge($this->documents(StudentDocumentWorkspace::query()->where('user_id', $user->id), $q, $types, $templates, $date, false,
                    fn ($w) => \App\Support\DocumentProgress::describe($w)['href']));
                $items = $items->merge($this->appointments(Appointment::query()->where('user_id', $user->id), $q, $date, false,
                    fn ($a) => route('student.appointments.show', $a->appointment_id, false)));
            }
            if ($user->isSignatory()) {
                // Documents they issued, and documents waiting for (or signed with) their signature
                $items = $items->merge($this->documents(StudentDocumentWorkspace::usingWorkflow()->issuedBy($user), $q, $types, [], $date, true,
                    fn ($w) => route('signatory.issued.show', $w->workspace_id, false), 'Issued by you'));
                $approvals = TransactionApproval::query()
                    ->where(fn ($a) => $a->where('acted_by', $user->id)->orWhereIn('id', TransactionApproval::assignableTo($user)->select('id')))
                    ->whereHas('workspace', fn ($w) => $this->matchDocument($w, $q, $types, [], $date, true))
                    ->with('workspace.user')->latest('id')->limit(self::LIMIT)->get();
                foreach ($approvals as $ap) {
                    $w = $ap->workspace;
                    $items->push([
                        'label' => $this->docName($w) . ($w->user ? ' · ' . $w->user->full_name : ''),
                        'hint' => trim(($w->transaction_code ?? '') . ' · ' . ($ap->status?->value === 'pending' ? 'Waiting for your signature' : 'You ' . ($ap->status?->value ?? 'acted'))),
                        'group' => 'Signing', 'icon' => 'bx-pen', 'url' => route('signatory.show', $ap->id, false),
                    ]);
                }
            }
        }

        return response()->json(['items' => $items->unique('url')->take(14)->values()]);
    }

    /** Document types whose name contains what was typed, e.g. "admission" → admission_slip. */
    private function documentTypesNamed(string $q): array
    {
        $needle = Str::lower($q);

        return collect(DocumentRegistry::all())
            ->filter(fn ($d) => str_contains(Str::lower($d->name()), $needle))
            ->map(fn ($d) => $d->key())->values()->all();
    }

    /** A date in the text ("Nov 5", "2026-11-05", "11/05/2026"), or null. */
    private function dateIn(string $q): ?string
    {
        if (! preg_match('/\d/', $q) && ! preg_match('/\b(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\b/i', $q)) {
            return null;
        }
        try {
            $d = Carbon::parse($q);

            return $d->year > 2000 && $d->year < 2100 ? $d->toDateString() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function matchDocument(Builder $w, string $q, array $types, array $templates, ?string $date, bool $byStudent): Builder
    {
        return $w->where(function ($s) use ($q, $types, $templates, $date, $byStudent) {
            $s->where('transaction_code', 'like', "%{$q}%");
            if ($types) {
                $s->orWhereIn('document_type', $types);
            }
            if ($templates) {
                $s->orWhereIn('template_id', $templates);
            }
            if ($date) {
                $s->orWhereDate('submitted_at', $date)->orWhereDate('created_at', $date);
            }
            if ($byStudent) {
                $s->orWhereHas('user', fn ($u) => $u
                    ->whereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$q}%"])
                    ->orWhere('student_number', 'like', "%{$q}%"));
            }
        });
    }

    private function documents(Builder $base, string $q, array $types, array $templates, ?string $date, bool $byStudent, \Closure $url, string $group = 'Documents'): array
    {
        $rows = $this->matchDocument($base, $q, $types, $templates, $date, $byStudent)
            ->with(['user', 'template'])->latest('updated_at')->limit(self::LIMIT)->get();

        return $rows->map(function ($w) use ($byStudent, $url, $group) {
            $status = $w->usesWorkflow()
                ? Str::headline((string) ($w->workflow_status?->value ?? $w->workflow_status))
                : ($w->isCompleted() ? 'Completed' : 'Print form');

            return [
                'label' => $this->docName($w) . ($byStudent && $w->user ? ' · ' . $w->user->full_name : ''),
                'hint' => implode(' · ', array_filter([$w->transaction_code, $status, ($w->submitted_at ?? $w->created_at)?->format('M j, Y')])),
                'group' => $group, 'icon' => 'bx-file', 'url' => $url($w),
            ];
        })->all();
    }

    private function appointments(Builder $base, string $q, ?string $date, bool $byStudent, \Closure $url): array
    {
        $rows = $base->where(function ($s) use ($q, $date, $byStudent) {
            $s->where('purpose', 'like', "%{$q}%")->orWhere('status', 'like', "{$q}%");
            if ($date) {
                $s->orWhereDate('appointment_date', $date);
            }
            if (ctype_digit(ltrim($q, '#'))) {
                $s->orWhere('queue_number', (int) ltrim($q, '#'));
            }
            if ($byStudent) {
                $s->orWhereHas('user', fn ($u) => $u
                    ->whereRaw("CONCAT_WS(' ', first_name, last_name) like ?", ["%{$q}%"])
                    ->orWhere('student_number', 'like', "%{$q}%"));
            }
        })->with('user')->orderByDesc('appointment_date')->limit(self::LIMIT)->get();

        return $rows->map(fn ($a) => [
            'label' => ($a->appointment_date?->format('D, M j, Y') ?? 'Appointment') . ' · ' . Str::ucfirst((string) $a->session) . ($byStudent && $a->user ? ' · ' . $a->user->full_name : ''),
            'hint' => implode(' · ', array_filter([Str::limit((string) $a->purpose, 60), Str::headline((string) $a->status), $a->queue_number ? 'Queue #' . $a->queue_number : null])),
            'group' => 'Appointments', 'icon' => 'bx-calendar', 'url' => $url($a),
        ])->all();
    }

    private function docName(StudentDocumentWorkspace $w): string
    {
        return DocumentRegistry::find((string) $w->document_type)?->name() ?? $w->template?->name ?? 'Document';
    }
}
