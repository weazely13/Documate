<?php

namespace App\Services\Workflow;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Freezes an online transaction as it stood at a moment (submitted, completed), so the record survives
 * later changes to the form's code: renamed or removed fields, a new layout, or the whole form removed.
 *
 *  - snapshot.document_name   the form's name at the time
 *  - snapshot.answers         every field: name, label and the answer as shown
 *  - snapshot.pages.{stage}   the rendered page, saved as HTML on the local disk
 */
class TransactionSnapshot
{
    public const DISK = 'local';

    public function capture(StudentDocumentWorkspace $ws, string $stage): void
    {
        $document = DocumentRegistry::find($ws->document_type);
        if (! $document) {
            return; // nothing to freeze from: the code is already gone
        }

        $snapshot = $ws->snapshot ?? [];
        $values = $ws->field_values ?? [];

        $snapshot['document_type'] = $ws->document_type;
        $snapshot['document_name'] = $document->name();
        $snapshot['answers'] = collect($document->fields() + $document->reviewerFields())
            ->reject(fn ($f) => ($f['type'] ?? '') === 'slots')
            ->map(fn ($f, $name) => [
                'name' => (string) $name,
                'label' => (string) ($f['label'] ?? $name),
                'value' => $document->formatValue($f, $values[$name] ?? null),
            ])
            ->values()
            ->all();

        try {
            $html = app(DocumentPdf::class)->previewHtml($ws->loadMissing('user'));
            $path = 'workflow-snapshots/' . Str::slug($ws->transaction_code ?: (string) $ws->workspace_id) . '-' . $stage . '.html';
            Storage::disk(self::DISK)->put($path, $html);
            $snapshot['pages'][$stage] = ['path' => $path, 'at' => now()->toIso8601String()];
        } catch (\Throwable $e) {
            report($e); // the answers are still frozen
        }

        $snapshot['captured_at'] = now()->toIso8601String();
        $ws->forceFill(['snapshot' => $snapshot])->saveQuietly();
    }

    /**
     * The saved page to show instead of drawing it live: the completed one for a completed transaction
     * (it always looks as it did when finished), or any saved page once the form's code is gone.
     */
    public static function savedPage(StudentDocumentWorkspace $ws): ?string
    {
        $pages = $ws->snapshot['pages'] ?? [];
        $codeGone = ! DocumentRegistry::has((string) $ws->document_type);

        $path = match (true) {
            $ws->workflow_status === TransactionStatus::Completed && isset($pages['completed']) => $pages['completed']['path'],
            $codeGone => ($pages['completed']['path'] ?? $pages['submitted']['path'] ?? null),
            default => null,
        };

        return $path && Storage::disk(self::DISK)->exists($path) ? Storage::disk(self::DISK)->get($path) : null;
    }

    /** True when there is something to show for this transaction even without its form's code. */
    public static function hasAny(StudentDocumentWorkspace $ws): bool
    {
        return ! empty($ws->snapshot['answers']) || ! empty($ws->snapshot['pages']);
    }
}
