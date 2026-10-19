<?php

namespace App\Workflow\Documents;

use App\Models\User;
use App\Services\Workflow\FormSupport;

/**
 * F-SDM-026 Rev. 1 (03-31-23): College Model Student Interview/Talent Presentation Rating Form.
 * A signatory (the interviewer) rates up to 10 interviewees on three 10-point criteria.
 * Every interviewee is picked from the student accounts, so each one is verified to have a record.
 * No student is notified: the record belongs to the interviewer. The VPSD office does the final verification.
 */
final class CollegeModelInterviewRating extends WorkflowDocument
{
    public const ROWS = 10;

    /** field suffix => column heading */
    public const CRITERIA = [
        'personality' => 'Personality',
        'communication' => 'Communication Skills',
        'talent' => 'Talent',
    ];

    public function key(): string { return 'college_model_interview_rating'; }
    public function name(): string { return 'College Model Student Interview/Talent Rating'; }
    public function description(): string { return 'An interviewer\'s ratings of College Model Student interviewees.'; }
    public function formCode(): string { return 'F-SDM-026 Rev. 1 (03-31-23)'; }

    public function issuedByOffice(): bool { return true; }
    public function issuedBySignatory(): bool { return true; }
    public function issuedToSelf(): bool { return true; }

    public function fields(): array
    {
        $fields = [];

        for ($i = 1; $i <= self::ROWS; $i++) {
            $fields["interviewee_{$i}_user_id"] = [
                'label' => "Interviewee {$i} account", 'type' => 'text', 'max' => 20,
                'hidden' => true, 'staff_only' => true, 'sensitive' => true, 'group' => 'rating_rows',
            ];
            $fields["interviewee_{$i}_name"] = [
                'label' => "Interviewee {$i}", 'type' => 'text', 'max' => 120,
                'group' => 'rating_rows', 'sensitive' => true,
            ];
            foreach (self::CRITERIA as $key => $label) {
                $fields["score_{$i}_{$key}"] = [
                    'label' => "{$label} (interviewee {$i})", 'type' => 'number',
                    'group' => 'rating_rows', 'sensitive' => true,
                    'rules' => ['integer', 'between:1,10'],
                ];
            }
        }

        return $fields;
    }

    /** An active Student/Officer account that is not the issuer. */
    private function eligible(int $id, ?int $issuerId): ?User
    {
        return User::query()
            ->whereKey($id)
            ->where('account_status', 'active')
            ->whereHas('role', fn ($q) => $q->whereIn('role_name', ['Student', 'Officer']))
            ->when($issuerId, fn ($q) => $q->where('id', '!=', $issuerId))
            ->first();
    }

    public function beforeIssue(array $answers): array
    {
        $issuerId = auth()->id();

        for ($i = 1; $i <= self::ROWS; $i++) {
            $id = (int) ($answers["interviewee_{$i}_user_id"] ?? 0);
            $student = $id ? $this->eligible($id, $issuerId) : null;

            if ($student) {
                // The name comes from the account, never from what was typed.
                $answers["interviewee_{$i}_user_id"] = (string) $student->id;
                $answers["interviewee_{$i}_name"] = FormSupport::systemValue($student, 'formatted_full_name');
                $answers["interviewee_{$i}_number"] = (string) $student->student_number;
            } else {
                $answers["interviewee_{$i}_user_id"] = null;
                $answers["interviewee_{$i}_number"] = null;
            }

            $name = trim((string) ($answers["interviewee_{$i}_name"] ?? ''));

            if ($name === '') {
                $answers["interviewee_{$i}_name"] = null;
                $answers["total_{$i}"] = null;
                foreach (array_keys(self::CRITERIA) as $key) {
                    $answers["score_{$i}_{$key}"] = null;
                }
                continue;
            }

            $sum = 0;
            foreach (array_keys(self::CRITERIA) as $key) {
                $sum += (int) ($answers["score_{$i}_{$key}"] ?? 0);
            }
            $answers["total_{$i}"] = (string) $sum;
        }

        return $answers;
    }

    public function check(array $values): ?string
    {
        $count = 0;
        $seen = [];

        for ($i = 1; $i <= self::ROWS; $i++) {
            $name = trim((string) ($values["interviewee_{$i}_name"] ?? ''));
            $hasScore = false;
            foreach (array_keys(self::CRITERIA) as $key) {
                $raw = $values["score_{$i}_{$key}"] ?? null;
                $hasScore = $hasScore || ($raw !== null && $raw !== '');
            }

            if ($name === '' && ! $hasScore) {
                continue;
            }
            if ($name === '') {
                return "Row {$i} has scores but no interviewee. Search for the student first.";
            }
            if (empty($values["interviewee_{$i}_number"])) {
                return "Row {$i}: pick {$name} from the search results so their student account can be verified.";
            }

            $uid = (string) $values["interviewee_{$i}_user_id"];
            if (isset($seen[$uid])) {
                return "Row {$i}: {$name} is listed more than once.";
            }
            $seen[$uid] = true;

            foreach (self::CRITERIA as $key => $label) {
                $raw = $values["score_{$i}_{$key}"] ?? null;
                $n = is_numeric($raw) ? (int) $raw : 0;
                if ($n < 1 || $n > 10 || (string) $n !== trim((string) $raw)) {
                    return "Row {$i} ({$name}): rate {$label} from 1 to 10.";
                }
            }
            $count++;
        }

        if ($count === 0) {
            return 'Add at least one interviewee.';
        }

        $user = auth()->user();
        if ($user?->signature_enabled && empty($user->signature_path)) {
            return 'Upload your signature in your profile before issuing this rating form.';
        }

        return null;
    }

    /** No approval steps: the interviewer's signature is captured at issue time. */
    public function chain(array $values = []): array { return []; }

    public function issueNote(): ?string
    {
        return 'Search for each interviewee and pick them from the list. Only students with an active account can be rated, and their name is taken from their account. Rate each from 1 to 10 on every criterion; the total is computed. No student is notified. You appear as the interviewer, signed with your profile signature, and the VPSD office then does the final verification.';
    }

    public function pdfView(): string { return 'pdf.college-model-interview-rating'; }

    public function summary(array $values): string
    {
        $n = 0;
        for ($i = 1; $i <= self::ROWS; $i++) {
            if (! empty($values["interviewee_{$i}_name"])) {
                $n++;
            }
        }

        return "Interview ratings · {$n} " . ($n === 1 ? 'interviewee' : 'interviewees');
    }
}