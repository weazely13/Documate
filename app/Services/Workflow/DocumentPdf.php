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
use App\Enums\TransactionStatus;

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

    /**
     * The exact HTML of the document, for on-screen preview. No file is written, nothing is audited.
     * Works on an unsaved workspace too (the student's live form), which simply has no approvals yet.
     *
     * $pending shows one signer's answers before they sign (the emailed-link page):
     * ['approval_id' => int, 'name' => string, 'values' => array, 'response' => array, 'signature' => ?data-URI].
     * Nothing is sealed or stored; the step still prints without a seal or signing time.
     */
    public function previewHtml(StudentDocumentWorkspace $ws, ?array $pending = null): string
    {
        $document = DocumentRegistry::get($ws->document_type);
        $html = view($document->pdfView(), $this->viewData($ws, preview: true, pending: $pending))->render();

        return str_ireplace('</head>', $this->previewSheet($html) . '</head>', $html);
    }

    /**
     * Turns the preview into one A4 sheet at the template's own page margins, with the verification
     * footer where Dompdf prints it. Content taller than the page is scaled down to fit, so the
     * preview is always a single page, and the frame reports its height so the host can size the iframe.
     */
    private function previewSheet(string $html): string
    {
        [$top, $right, $bottom, $left] = [40, 54, 96, 54];
        if (preg_match('/@page\s*\{\s*margin:\s*([^;}]+)/i', $html, $m)) {
            $v = array_map('floatval', preg_split('/\s+/', trim(str_replace('pt', '', $m[1]))));
            [$top, $right, $bottom, $left] = match (count($v)) {
                1 => [$v[0], $v[0], $v[0], $v[0]],
                2 => [$v[0], $v[1], $v[0], $v[1]],
                3 => [$v[0], $v[1], $v[2], $v[1]],
                default => array_slice($v, 0, 4),
            };
        }
        // .foot { bottom: -78pt } sits below the content box, inside the bottom margin
        $footDrop = preg_match('/\.foot\s*\{[^}]*bottom:\s*-([\d.]+)pt/i', $html, $f) ? (float) $f[1] : 78;
        $footBottom = max(0, $bottom - $footDrop);

        return <<<HTML
    <style>
    html{background:#e7e5e1!important;overflow-x:hidden}
    body{box-sizing:border-box!important;width:595.28pt!important;max-width:none!important;height:841.89pt!important;min-height:0!important;overflow:hidden!important;margin:18pt auto!important;padding:{$top}pt {$right}pt {$bottom}pt {$left}pt!important;background:#fff!important;box-shadow:0 1px 3px rgba(13,37,61,.12),0 8px 24px rgba(0,55,112,.08)!important;position:relative!important;border-radius:0!important}
    .dm-content{display:flow-root;transform-origin:top center}
    .foot{position:absolute!important;left:{$left}pt!important;right:{$right}pt!important;bottom:auto!important}
    .ph-qr{display:inline-flex;align-items:center;justify-content:center;text-align:center;box-sizing:border-box;border:1px dashed #b5b5b5;border-radius:3px;background:repeating-linear-gradient(45deg,#f8f8f8 0 4px,#efefef 4px 8px);color:#8a8a8a;font-family:'DejaVu Sans',sans-serif;font-size:5.5pt;font-weight:bold;line-height:1.25;letter-spacing:.3pt;text-transform:uppercase;white-space:normal}
    .ph-text{display:inline-block;padding:0 4pt;border:1px dashed #c4c4c4;border-radius:2pt;background:#f6f6f6;color:#8a8a8a;font-family:'DejaVu Sans',sans-serif;font-size:7pt;font-style:italic;font-weight:normal;letter-spacing:0}
    </style>
    <script>
    (function(){
    var PT=4/3, PAGE=841.89*PT, ROOM=(841.89-{$top}-{$bottom})*PT, FOOT_BOTTOM={$footBottom}*PT, embedded=window.self!==window.top;
    function content(){
        var c=document.querySelector('.dm-content'); if(c) return c;
        var b=document.body; c=document.createElement('div'); c.className='dm-content';
        Array.prototype.slice.call(b.childNodes).forEach(function(n){
            if(n.nodeType===1&&n.matches('.foot,.watermark,script,style')) return;
            c.appendChild(n);
        });
        b.insertBefore(c,b.firstChild); return c;
    }
    function layout(){
        var b=document.body; if(!b) return;
        var c=content(); b.style.zoom=''; c.style.transform='';
        var h=c.getBoundingClientRect().height;
        if(h>ROOM) c.style.transform='scale('+(ROOM/h).toFixed(4)+')';
        var foot=document.querySelector('.foot');
        if(foot) foot.style.top=(PAGE-FOOT_BOTTOM-foot.offsetHeight)+'px';
        if(embedded){
            parent.postMessage({documatePreviewHeight:document.documentElement.scrollHeight},'*');
        }else{
            var need=b.offsetWidth+48, have=document.documentElement.clientWidth;
            if(have<need) b.style.zoom=(have/need).toFixed(3);
        }
    }
    addEventListener('load',layout); addEventListener('resize',function(){ if(!embedded) layout(); });
    if(document.fonts&&document.fonts.ready) document.fonts.ready.then(layout);
    })();
    </script>
    HTML;
    }

    /** Single source of data for BOTH the official PDF and the on-screen preview. */
    public function viewData(StudentDocumentWorkspace $ws, bool $preview = false, ?array $pending = null): array
    {
        $pending = $preview ? $pending : null; // never on the official PDF
        $document = DocumentRegistry::get($ws->document_type);

        // A preview shows the real QR code and fingerprint only once the document is issued.
        // Until then the footer carries placeholders, so nothing verifiable leaks from a draft.
        $reveal = ! $preview || $ws->workflow_status === TransactionStatus::Completed;

        $verifyUrl = ($reveal && $ws->verification_token)
            ? route('document.verify', $ws->verification_token)
            : null;

        return [
            'v' => array_merge($ws->printedValues(), (array) ($pending['values'] ?? [])),
            'code' => $ws->transaction_code,
            'issuedAt' => $ws->issued_at,
            'signers' => $this->signers($ws, $document, $pending),
            'copies' => $document->pdfCopies(),
            'formCode' => $document->formCode(),
            'seal' => $this->dataUri('images/lnu-seal.png', public: true),
            'verifyUrl' => $verifyUrl,
            'qr' => $verifyUrl ? $this->qr($verifyUrl) : null,
            'fingerprint' => ($reveal && $ws->content_hash) ? Seal::fingerprint($ws->content_hash) : null,
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
    private function signers(StudentDocumentWorkspace $ws, $document, ?array $pending = null): array
    {
        $approvals = $ws->approvals()->with('actor')->orderBy('step_order')->get();

        if ($approvals->isEmpty()) {
            return collect($document->chain($ws->field_values ?? []))
                ->map(fn ($s) => $this->blankSigner($s['label'] ?? ''))
                ->values()->all();
        }

        return $approvals->map(function ($a) use ($pending) {
            if ($a->status !== ApprovalStatus::Approved) {
                if ($pending && (int) ($pending['approval_id'] ?? 0) === (int) $a->id) {
                    $sig = (string) ($pending['signature'] ?? '');

                    return [
                        'label' => $a->label,
                        'name' => (string) ($pending['name'] ?? ''),
                        'signature' => str_starts_with($sig, 'data:image/png;base64,') ? $sig : null,
                        'signed_at' => null,
                        'seal' => '',
                        'response' => (array) ($pending['response'] ?? []),
                    ];
                }

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