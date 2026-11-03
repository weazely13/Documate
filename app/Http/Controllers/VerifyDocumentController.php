<?php

namespace App\Http\Controllers;

use App\Enums\TransactionStatus;
use App\Models\StudentDocumentWorkspace;
use App\Services\Workflow\Seal;
use App\Workflow\Documents\DocumentRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * PUBLIC page (no login): whoever holds a printed or PDF copy scans the QR code and sees
 * whether the record behind it is genuine and unaltered. Reasons or other fields marked
 * 'sensitive' => true in the document definition are never shown here.
 */
class VerifyDocumentController extends Controller
{
    public function show(string $token)
    {
        $ws = $this->find($token);

        if (! $ws) {
            return $this->private(response()->view('verify.document', ['found' => false], 404));
        }

        $document = DocumentRegistry::find($ws->document_type);
        $values = $ws->printedValues();

        $details = [
            'Student' => $values['student_name'] ?? '—',
            'Program / Year / Section' => $values['student_program'] ?? '—',
        ];

        foreach ($document?->allFields() ?? [] as $name => $f) {
            if (! empty($f['sensitive'])) {
                continue;
            }

            $details[$f['label']] = $document->formatValue($f, $values[$name] ?? null) ?: '—';
        }

        return $this->private(response()->view('verify.document', [
            'found' => true,
            'ws' => $ws,
            'documentName' => $document?->name() ?? $ws->document_type,
            'report' => Seal::verify($ws),
            'details' => $details,
            'token' => $token,
            'voided' => $ws->workflow_status === TransactionStatus::Voided,
            'voidedAt' => $ws->voided_at,
            'replacement' => $ws->superseded_by
                ? StudentDocumentWorkspace::whereKey($ws->superseded_by)->where('workflow_status', TransactionStatus::Completed->value)->value('transaction_code')
                : null,
        ]));
    }

    public function lookup()
    {
        return $this->private(response()->view('verify.lookup'));
    }

    /**
     * These pages show a person's details to whoever holds the link: keep them out of search engines and
     * shared caches, and never send the link (it is the key) to other sites in the Referer header.
     */
    private function private($response)
    {
        return $response->withHeaders([
            'X-Robots-Tag' => 'noindex, nofollow',
            'Referrer-Policy' => 'no-referrer',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function check(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:300'],
            'fingerprint' => ['nullable', 'string', 'max:32'],
        ]);

        // A pasted verification link (what the QR code holds) opens the record directly: the link is the key.
        if (preg_match('~verify-document/([A-Za-z0-9]{30,64})~', $data['reference'], $m) && $this->find($m[1])) {
            return redirect()->route('document.verify', $m[1]);
        }

        if (blank($data['fingerprint'] ?? null)) {
            return redirect()->route('document.lookup')->withInput()->withErrors(['fingerprint' => 'Enter the Fingerprint printed under the Reference No., or paste the link from the QR code.']);
        }

        $ref = strtoupper(trim($data['reference']));
        $fp = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $data['fingerprint']));

        $ws = StudentDocumentWorkspace::query()
            ->where('transaction_code', $ref)
            ->whereIn('workflow_status', [TransactionStatus::Completed->value, TransactionStatus::Voided->value])
            ->first();

        // One generic message so nobody can probe which reference numbers exist.
        $ok = $ws
            && filled($ws->content_hash)
            && hash_equals(strtoupper(substr($ws->content_hash, 0, 16)), $fp);

        if (! $ok) {
            // Always the verify page (the form also sits on the home page), with one message for every miss.
            return redirect()->route('document.lookup')->withInput()->withErrors(['reference' => 'No completed document matches that Reference No. and Fingerprint. Check both against the bottom of the document.']);
        }

        return redirect()->route('document.verify', $ws->verification_token);
    }
    private function find(string $token): ?StudentDocumentWorkspace
    {
        if (strlen($token) < 30) {
            return null;
        }

        return StudentDocumentWorkspace::query()
            ->where('verification_token', $token)
            ->whereIn('workflow_status', [TransactionStatus::Completed->value, TransactionStatus::Voided->value])
            ->first();
    }

    /** Optional: upload the PDF you were handed and compare it with the one DocuMate issued. */
    public function checkFile(Request $request, string $token)
    {
        $ws = $this->find($token);
        abort_unless($ws, 404);

        $request->validate(['pdf' => ['required', 'file', 'mimes:pdf', 'max:5120']]);

        $uploaded = hash_file('sha256', $request->file('pdf')->getRealPath());
        $matches = filled($ws->pdf_sha256) && hash_equals($ws->pdf_sha256, $uploaded);

        return redirect()->route('document.verify', $token)->with('file_check', $matches ? 'match' : 'mismatch');
    }

}