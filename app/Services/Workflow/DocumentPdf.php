<?php

namespace App\Services\Workflow;

use App\Models\StudentDocumentWorkspace;
use App\Workflow\Documents\DocumentRegistry;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Enums\ApprovalStatus;

/**
 * Builds the official PDF from the sealed transaction record (never from live account data).
 * Requires: composer require barryvdh/laravel-dompdf endroid/qr-code
 */
class DocumentPdf
{
    /** Official PDFs and signature images live on this private disk. */
    public const DISK = 'local';

    public function generateFor(StudentDocumentWorkspace $ws): string
    {
        $document = DocumentRegistry::get($ws->document_type);

        [$size, $orientation] = $document->pdfPaper();

        $pdf = Pdf::loadView($document->pdfView(), $this->viewData($ws))
            ->setPaper($size, $orientation);

        $output = $pdf->output();
        $path = "workflow-documents/{$ws->transaction_code}.pdf";
        Storage::disk(self::DISK)->put($path, $output);

        $ws->forceFill([
            'generated_pdf_path' => $path,
            'last_generated_at' => now(),
            'pdf_sha256' => hash('sha256', $output),
        ])->save();

        app(TransactionWorkflow::class)->recordDocumentGenerated($ws, $path);

        return $path;
    }

    /** The exact HTML of the document, for on-screen preview. No file is written, nothing is audited. */
    public function previewHtml(StudentDocumentWorkspace $ws): string
    {
        $document = DocumentRegistry::get($ws->document_type);
        $html = view($document->pdfView(), $this->viewData($ws, preview: true))->render();

        $fix = <<<'HTML'
    <style>html{overflow-x:hidden}body{width:520pt!important;max-width:none!important}</style>
    <script>
    (function(){
    function fit(){
        var b=document.body; b.style.zoom='';
        var need=b.offsetWidth+40, have=document.documentElement.clientWidth;
        if(have<need) b.style.zoom=(have/need).toFixed(3);
    }
    addEventListener('DOMContentLoaded',fit); addEventListener('resize',fit);
    })();
    </script>
    HTML;

        return str_ireplace('</head>', $fix . '</head>', $html);
    }

    /** Single source of data for BOTH the official PDF and the on-screen preview. */
    public function viewData(StudentDocumentWorkspace $ws, bool $preview = false): array
    {
        $document = DocumentRegistry::get($ws->document_type);

        $verifyUrl = (! $preview && $ws->verification_token)
            ? route('document.verify', $ws->verification_token)
            : null;

        return [
            'v' => $ws->field_values ?? [],
            'code' => $ws->transaction_code,
            'issuedAt' => $ws->issued_at,
            'signers' => $this->signers($ws, $document),
            'copies' => $document->pdfCopies(),
            'formCode' => $document->formCode(),
            'seal' => $this->dataUri('images/lnu-seal.png', public: true),
            'verifyUrl' => $verifyUrl,
            'qr' => $verifyUrl ? $this->qr($verifyUrl) : null,
            'fingerprint' => Seal::fingerprint($ws->content_hash),
            'preview' => $preview,
            'submittedAt' => $ws->submitted_at,
            'studentSignature' => $this->dataUri($ws->field_values['student_signature_path'] ?? null),
            'reporterSignature' => $this->dataUri($ws->field_values['reporter_signature_path'] ?? null),
            'studentSigner'  => $this->partySigner($ws, 'student',  'student_name',  'student_signature_path',  'student_name_detail'),
            'reporterSigner' => $this->partySigner($ws, 'reporter', 'reporter_name', 'reporter_signature_path', 'reporter_name_detail'),
        ];
    }

    /**
     * One entry per signing step, in order. Steps not yet signed are blank so the
     * preview shows an empty signature line instead of a fake signature.
     */
    private function signers(StudentDocumentWorkspace $ws, $document): array
    {
        $approvals = $ws->approvals()->with('actor')->orderBy('step_order')->get();

        if ($approvals->isEmpty()) {
            return collect($document->chain($ws->field_values ?? []))
                ->map(fn ($s) => $this->blankSigner($s['label'] ?? ''))
                ->values()->all();
        }

        return $approvals->map(function ($a) {
            if ($a->status !== ApprovalStatus::Approved) {
                return $this->blankSigner((string) $a->label);
            }
            $img = $this->dataUri($a->signature_path);
            return [
                'label' => $a->label,                       // <-- new
                'name' => $a->signer_name ?: ($a->actor ? $a->actor->full_name : ''),
                'detail' => FormSupport::nameDetail($a->actor),
                'signature' => $img,
                'no_image' => ! $img,
                'signed_at' => $a->acted_at,
                'seal' => Seal::shortSeal($a->seal),
                'response' => $a->response ?? [],
            ];
        })->values()->all();
    }

    private function blankSigner(string $label = ''): array
    {
        return ['label' => $label, 'name' => '', 'signature' => null, 'signed_at' => null, 'seal' => '', 'response' => []];
    }

    private function qr(string $url): ?string
    {
        try {
            $qr = method_exists(QrCode::class, 'create')
                ? QrCode::create($url)->setSize(220)->setMargin(4)          // v4
                : new QrCode(data: $url, size: 220, margin: 4);             // v5+

            return (new PngWriter())->write($qr)->getDataUri();
        } catch (\Throwable $e) {
            Log::error('DocuMate QR generation failed: ' . $e->getMessage());
            report($e);

            return null;
        }
    }

    private function dataUri(?string $path, bool $public = false): ?string
    {
        if (! $path) {
            return null;
        }

        try {
            if ($public) {
                $file = public_path($path);
                $bytes = is_file($file) ? file_get_contents($file) : null;
            } else {
                $bytes = Storage::disk(self::DISK)->exists($path) ? Storage::disk(self::DISK)->get($path) : null;
            }
        } catch (\Throwable) {
            $bytes = null;
        }

        if (! $bytes) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($bytes);
    }
    private function partySigner(StudentDocumentWorkspace $ws, string $kind, string $nameKey, string $pathKey, string $detailKey): array
    {
        $v = $ws->field_values ?? [];
        $name = (string) ($v[$nameKey] ?? '');
        $path = $v[$pathKey] ?? null;
        $img = $this->dataUri($path);
        $at = $ws->submitted_at;

        return [
            'name' => $name,
            'detail' => (string) ($v[$detailKey] ?? ''),
            'signature' => $img,
            'no_image' => $name !== '' && $at && ! $img,
            'signed_at' => $at,
            'seal' => ($name !== '' && $at) ? Seal::sealParty($ws, $kind, $name, $path) : '',
            'response' => [],
        ];
    }
}