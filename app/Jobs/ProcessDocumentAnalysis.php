<?php

namespace App\Jobs;

use App\Models\DocumentScanEvent;
use App\Models\StudentDocumentWorkspace;
use App\Services\DocumentAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessDocumentAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $backoff = 5;

    public function __construct(public int $workspaceId) {}

    /**
     * Global lock key (same string for every job of this class) means only
     * ONE of these ever runs at once across the whole app, regardless of how
     * many queue workers are running. Every other pending job is released
     * back onto the queue and retried after a short delay — this is what
     * keeps us under Gemini's requests-per-minute / token-per-minute limits.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('document-analysis-global'))
                ->releaseAfter(8)   // if blocked, try again in 8s
                ->expireAfter(180), // safety: force-release the lock after 3 min in case a job dies mid-run
        ];
    }

    public function handle(): void
    {
        $workspace = StudentDocumentWorkspace::with('template.currentVersion.fields')->find($this->workspaceId);

        if (!$workspace || !$workspace->supporting_file_path) {
            return;
        }

        $this->logEvent($workspace, 'scanning', 'Preparing your document photo…');
        $this->updateStage($workspace, 'Preparing your document photo…');

        $filePath = $workspace->supporting_file_path;
        $documentName = $workspace->template?->name ?? 'document';

        $fieldsCollection = $workspace->template?->currentVersion?->fields ?? collect();
        $fieldValues = $workspace->field_values ?? [];
        \Log::info('field_values keys', ['keys' => array_keys($fieldValues)]);

        $expectedFields = $fieldsCollection
            ->filter(fn ($f) => $f->field_type !== 'paragraph')
            ->map(function ($f) use ($fieldValues) {
                $key = $f->name ? Str::slug($f->name, '_') : ('field_' . $f->field_id);

                return [
                    'label' => $this->fieldDisplayName($f),
                    'expected_value' => $fieldValues[$key] ?? null,
                ];
            })
            ->filter(fn ($f) => filled($f['label']))
            ->values()
            ->all();

        $referenceImage = $this->buildReferenceImage($workspace);

        $this->updateStage($workspace, 'Sending photo to the AI review service — this can take up to a minute…');
        $this->logEvent($workspace, 'scanning', 'Sending photo to the AI review service…');

        $result = DocumentAnalysisService::analyze($filePath, $documentName, $expectedFields, $referenceImage);

        $this->updateStage($workspace, 'Reviewing results and verifying field values…');

        if (!$result['success']) {
            $reason = $this->technicalFailureReason($result);
            $this->rejectAndDiscard($workspace, $filePath, $result, $reason);
            $this->logEvent($workspace, 'failed', $reason);
            return;
        }

        $approved = $result['matches_document']
            && $result['readable']
            && $result['fields_complete']
            && $result['has_signature_or_stamp']
            && ($result['fields_match_expected'] ?? true);

        if (!$approved) {
            $reason = $this->buildRejectionReason($result, $documentName);
            $this->rejectAndDiscard($workspace, $filePath, $result, $reason);
            $this->logEvent($workspace, 'rejected', $reason);
            return;
        }

        $this->updateStage($workspace, 'Finalizing…');

        $workspace->update([
            'processing' => false,
            'processing_stage' => null,
            'status' => 'completed',
            'completed_at' => $workspace->completed_at ?? now(),
            'analysis_status' => 'approved',
            'analysis_summary' => $result['summary'],
            'analysis_data' => $result,
            'ocr_text' => $result['extracted_text'],
            'rejection_reason' => null,
            'analyzed_at' => now(),
        ]);

        $this->logEvent($workspace, 'approved', $result['summary'] ?? 'Document verified.');
    }
    private function fieldDisplayName($field): string
    {
        $generic = ['text', 'date', 'number', 'paragraph', 'email', 'textarea',
                    strtolower((string) $field->data_type), strtolower((string) $field->field_type)];

        $label = trim((string) $field->label);

        // Use the label only if it's a real, descriptive one
        if ($label !== '' && ! in_array(strtolower($label), $generic, true)) {
            return $label;
        }

        // Otherwise fall back to the field's name ("student_name" becomes "Student Name")
        return filled($field->name) ? Str::headline($field->name) : $label;
    }
    private function logEvent(StudentDocumentWorkspace $workspace, string $status, ?string $message = null): void
    {
        try {
            DocumentScanEvent::create([
                'workspace_id' => $workspace->workspace_id,
                'status'       => $status,
                'message'      => $message ? \Illuminate\Support\Str::limit($message, 1000) : null,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Failed to log scan event', ['status' => $status, 'error' => $e->getMessage()]);
        }
    }

    // Safety net if the job dies anywhere else (timeout, exception)
    public function failed(\Throwable $e): void
    {
        $workspace = StudentDocumentWorkspace::find($this->workspaceId);
        if (! $workspace) return;

        $workspace->update(['processing' => false, 'processing_stage' => null]);
        $this->logEvent($workspace, 'failed', 'The review could not be completed. Please try again.');
    }

    private function updateStage(StudentDocumentWorkspace $workspace, string $stage): void
    {
        $workspace->update(['processing_stage' => $stage]);
    }

    private function buildReferenceImage(StudentDocumentWorkspace $workspace): ?array
    {
        if (!$workspace->reference_image_path) {
            return null;
        }

        $fullPath = Storage::disk('public')->path($workspace->reference_image_path);

        if (!file_exists($fullPath)) {
            return null;
        }

        return [
            'mime_type' => mime_content_type($fullPath) ?: 'image/jpeg',
            'data' => base64_encode(file_get_contents($fullPath)),
        ];
    }

    private function technicalFailureReason(array $result): string
    {
        return match ($result['error_code']) {
            'file_missing' =>
                "We couldn't find the uploaded file on our server. Please try uploading the photo again.",
            'content_blocked' =>
                "The photo couldn't be reviewed because our safety system flagged it. Make sure the photo only shows the document itself, then try again.",
            'api_error_429' =>
                'Our AI review service is currently handling a lot of requests. Please try again in a few minutes.',
            'request_exception', 'empty_response', 'api_error_500', 'api_error_502', 'api_error_503' =>
                'Our AI review service is temporarily unavailable. Please try uploading the same photo again in a few minutes.',
            'unparseable_response', 'invalid_json' =>
                "We had trouble reading the AI review result. Please try uploading again.",
            'missing_api_key' =>
                'Document review is temporarily unavailable due to a system issue.',
            default =>
                "We couldn't process this image right now due to a system issue. Please try uploading again.",
        };
    }

    private function buildRejectionReason(array $result, string $documentName): string
    {
        if (!$result['matches_document']) {
            $found = $result['detected_document_type'] ?? '';
            $found = $found ? " It looks like a {$found}." : '';

            return "The uploaded photo doesn't appear to match the \"{$documentName}\" document.{$found} Please upload a clear photo of the correct document.";
        }

        if (!$result['readable']) {
            return 'The photo is too unclear to read. Please upload a clearer photo.';
        }

        $issues = [];

        if (!$result['fields_complete']) {
            $missing = $result['missing_fields'] ?? [];
            $issues[] = $missing
                ? 'the following field(s) appear blank: ' . implode(', ', $missing)
                : 'some required fields appear blank';
        }

        if (!$result['has_signature_or_stamp']) {
            $issues[] = 'no signature or official stamp was detected';
        }

        if (! ($result['fields_match_expected'] ?? true)) {
            $mismatched = collect($result['field_matches'] ?? [])
                ->where('matches', false)
                ->pluck('label')
                ->filter()
                ->implode(', ');

            $issues[] = $mismatched
                ? "these field(s) don't match what was entered when the document was created: {$mismatched}"
                : 'some field values on the photo do not match what was entered when the document was created';
        }

        $issueText = implode(' and ', $issues);

        return "This upload couldn't be marked complete because {$issueText}. Please have the document properly filled out and signed/stamped, then upload a new photo.";
    }

    private function rejectAndDiscard(
        StudentDocumentWorkspace $workspace,
        string $filePath,
        array $result,
        string $reason
    ): void {
        Storage::disk('public')->delete($filePath);

        $workspace->update([
            'processing' => false,
            'processing_stage' => null,
            'supporting_file_path' => null,
            'supporting_uploaded_at' => null,
            'analysis_status' => 'rejected',
            'analysis_summary' => $result['summary'] ?? null,
            'analysis_data' => $result,
            'rejection_reason' => $reason,
            'analyzed_at' => now(),
        ]);
    }
}