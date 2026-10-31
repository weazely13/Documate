<?php

namespace App\Workflow\Documents;

use App\Models\StudentDocumentWorkspace;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Workflow\Documents\StudentAssistantshipApplication as SA;

/**
 * F-SDM-019 Rev. 1 (03-31-23): Student Assistantship Endorsement Form.
 * The student assistant requests it: they pick their approved Student Assistantship Application
 * (its schedule is copied in and sealed), then enter the office and starting date.
 * The Vice President signs it.
 */
final class StudentAssistantshipEndorsement extends WorkflowDocument
{
    public function category(): \App\Enums\DocumentCategory { return \App\Enums\DocumentCategory::Requests; }

    public function key(): string { return 'student_assistantship_endorsement'; }
    public function name(): string { return 'Student Assistantship Endorsement'; }
    public function description(): string { return 'Request your endorsement to the office you were assigned to, using your approved application.'; }
    public function formCode(): string { return 'F-SDM-019 Rev. 1 (03-31-23)'; }

    public function sourceDocumentType(): ?string { return 'student_assistantship_application'; }

    public function fields(): array
    {
        $applications = $this->applicationOptions();

        $fields = [
            'source_application' => [
                'label' => 'Approved application', 'type' => 'select', 'required' => true,
                'options' => array_keys($applications),
                'option_labels' => $applications,
                'rules' => [Rule::in(array_keys($applications))],
                'help' => $applications ? 'Its schedule is copied into the endorsement.' : 'You have no approved Student Assistantship Application yet.',
            ],
            'office_name' => [
                'label' => 'Name of Office', 'type' => 'text', 'required' => true, 'max' => 120,
                'placeholder' => 'e.g. Registrar\'s Office',
            ],
            'start_date' => [
                'label' => 'Starting date', 'type' => 'date', 'required' => true, 'future' => true,
            ],
            // Filled by fillFromSource().
            'source_application_id' => ['label' => 'Source application ID', 'type' => 'text', 'max' => 20, 'hidden' => true, 'staff_only' => true, 'sensitive' => true],
        ];

        foreach (SA::DAYS as $k => [$label]) {
            foreach (['am' => 'AM', 'pm' => 'PM'] as $h => $H) {
                foreach (['from', 'to'] as $p) {
                    $fields["{$k}_{$h}_{$p}"] = [
                        'label' => "{$label}: {$H} {$p}", 'type' => 'time', 'hidden' => true,
                        'group' => 'schedule', 'sensitive' => true,
                    ];
                }
            }
        }

        return $fields;
    }

    /** The signed-in student's approved applications: transaction code => label. */
    private function applicationOptions(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        return StudentDocumentWorkspace::query()
            ->where('user_id', $user->id)
            ->where('document_type', $this->sourceDocumentType())
            ->whereIn('workflow_status', ['approved', 'completed'])
            ->latest('submitted_at')
            ->get()
            ->mapWithKeys(fn ($w) => [
                (string) $w->transaction_code => $w->transaction_code . ' · ' . SA::fmt(SA::weeklyTotal($w->field_values ?? [])) . ' hrs/week',
            ])
            ->all();
    }

    /** The student's own approved application with this code, or null. */
    public function sourceFor(\App\Models\User $user, ?string $code): ?StudentDocumentWorkspace
    {
        return $code ? StudentDocumentWorkspace::query()
            ->where('user_id', $user->id)
            ->where('document_type', $this->sourceDocumentType())
            ->where('transaction_code', $code)
            ->whereIn('workflow_status', ['approved', 'completed'])
            ->first() : null;
    }

    public function fillFromSource(array $answers, StudentDocumentWorkspace $source): array
    {
        $src = $source->field_values ?? [];

        foreach (SA::DAYS as $k => $_) {
            foreach (['am', 'pm'] as $h) {
                foreach (['from', 'to'] as $p) {
                    $t = $src["{$k}_{$h}_{$p}"] ?? null;
                    $answers["{$k}_{$h}_{$p}"] = $t ? substr((string) $t, 0, 5) : null;
                }
            }
        }

        $answers['source_application_id'] = (string) $source->workspace_id;
        $answers['source_application'] = (string) $source->transaction_code;

        return $answers;
    }

    public function check(array $values): ?string
    {
        // The student form checks its raw input, before the schedule is copied in.
        if ($user = auth()->user()) {
            $source = $this->sourceFor($user, $values['source_application'] ?? null);
            if (! $source) {
                return 'Choose one of your approved Student Assistantship Applications.';
            }
            $values = $this->fillFromSource($values, $source);
        }

        return SA::weeklyTotal($values) > 0
            ? null
            : 'The chosen application has no vacant periods to copy.';
    }

    public function chain(array $values = []): array
    {
        return [['role' => 'vpsd', 'label' => 'Signed']];
    }

    public function pdfView(): string { return 'pdf.student-assistantship-endorsement'; }

    public function summary(array $values): string
    {
        $date = ! empty($values['start_date']) ? Carbon::parse($values['start_date'])->format('M j, Y') : null;

        return collect([$values['office_name'] ?? null, $date ? "starts {$date}" : null])->filter()->implode(' · ');
    }
}