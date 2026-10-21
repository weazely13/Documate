<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Document Verification · DocuMate</title>
    <style>
        :root { --blue:#2A57B4; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background:#f8fafc; color:var(--ink); }
        .wrap { max-width: 640px; margin: 0 auto; padding: 24px 16px 48px; }
        .brand { font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--blue); }
        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:20px; margin-top:16px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
        .banner { border-radius:14px; padding:18px 20px; margin-top:14px; }
        .ok   { background:#ecfdf5; border:1px solid #a7f3d0; color:#065f46; }
        .bad  { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
        .none { background:#f1f5f9; border:1px solid var(--line); color:#334155; }
        .banner h1 { margin:0; font-size:20px; }
        .banner p { margin:6px 0 0; font-size:14px; line-height:1.5; }
        h2 { margin:0 0 10px; font-size:12px; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
        dl { margin:0; display:grid; grid-template-columns: 1fr; gap:10px; }
        dt { font-size:12px; color:var(--muted); }
        dd { margin:2px 0 0; font-size:15px; font-weight:600; word-break:break-word; white-space: pre-line; }
        ul { list-style:none; margin:0; padding:0; }
        li { display:flex; gap:10px; align-items:flex-start; padding:10px 0; border-top:1px solid var(--line); }
        li:first-child { border-top:0; padding-top:0; }
        .dot { flex:none; width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; color:#fff; }
        .dot.y { background:#10b981; } .dot.n { background:#ef4444; }
        .small { font-size:12px; color:var(--muted); }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; }
        input[type=file] { font-size:13px; max-width:100%; }
        button { background:var(--blue); color:#fff; border:0; border-radius:10px; padding:9px 14px; font-weight:600; cursor:pointer; }
        .note { font-size:12px; color:var(--muted); line-height:1.5; margin-top:16px; }
        .flash { margin-top:12px; font-size:14px; font-weight:600; }
        .flash.ok { color:#065f46; background:none; border:0; } .flash.bad { color:#991b1b; background:none; border:0; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">DocuMate · VPSD Document Verification</div>

    @if (! $found)
        <div class="banner none">
            <h1>Record not found</h1>
            <p>This code does not match any completed document. Check that you scanned the whole QR code or typed the full link. If the link is correct, do not treat the document as genuine.</p>
        </div>
    @else
        @if ($voided ?? false)
            <div class="banner bad">
                <h1>&#10007; This document has been VOIDED</h1>
                <p>
                    The VPSD office withdrew this document on {{ $voidedAt?->format('F j, Y') }}. Do not accept it.
                    @if ($replacement ?? null)
                        A corrected document was issued under Reference <span class="mono">{{ $replacement }}</span>.
                    @endif
                    Contact the VPSD office if you have questions.
                </p>
            </div>
        @elseif ($report['valid'])
            <div class="banner ok">
                <h1>&#10003; Authentic and unaltered</h1>
                <p>This document was issued by DocuMate. Its contents and every signature below match the sealed record. Nothing has changed since it was signed.</p>
            </div>
        @else
            <div class="banner bad">
                <h1>&#10007; Verification failed</h1>
                <p>The record behind this code does not match its seal. Do not accept this document. Please contact the VPSD office.</p>
            </div>
        @endif

        <div class="card">
            <h2>{{ $documentName }}</h2>
            <dl>
                <div><dt>Reference</dt><dd class="mono">{{ $ws->transaction_code }}</dd></div>
                @foreach ($details as $label => $value)
                    <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
                @endforeach
                <div><dt>Date issued</dt><dd>{{ $ws->issued_at?->format('F j, Y g:i A') ?? '—' }}</dd></div>
                <div><dt>Document fingerprint</dt><dd class="mono">{{ $report['fingerprint'] }}</dd></div>
            </dl>
            <p class="small" style="margin:12px 0 0;">Compare these details and the fingerprint with the printed copy in your hands.</p>
        </div>

        <div class="card">
            <h2>Signatures</h2>
            <ul>
                @forelse ($report['signers'] as $s)
                    <li>
                        <span class="dot {{ $s['ok'] ? 'y' : 'n' }}">{{ $s['ok'] ? '✓' : '✕' }}</span>
                        <div>
                            <strong>{{ $s['name'] ?: '—' }}</strong>
                            <div class="small">{{ $s['role'] }} · {{ $s['label'] }}</div>
                            <div class="small">
                                {{ $s['signed_at']?->format('F j, Y g:i A') ?? '' }}
                                @if ($s['seal']) · Seal <span class="mono">{{ $s['seal'] }}</span> @endif
                            </div>
                        </div>
                    </li>
                @empty
                    <li><div class="small">This document did not require signatures.</div></li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <h2>Checks</h2>
            <ul>
                <li><span class="dot {{ $report['content_ok'] ? 'y' : 'n' }}">{{ $report['content_ok'] ? '✓' : '✕' }}</span><div>Document content matches the sealed fingerprint</div></li>
                <li><span class="dot {{ $report['signers_ok'] ? 'y' : 'n' }}">{{ $report['signers_ok'] ? '✓' : '✕' }}</span><div>Every signature seal and signature image is intact</div></li>
                <li><span class="dot {{ $report['audit_ok'] ? 'y' : 'n' }}">{{ $report['audit_ok'] ? '✓' : '✕' }}</span><div>Activity history has not been altered</div></li>
            </ul>
        </div>

        <div class="card">
            <h2>Have the PDF file?</h2>
            <p class="small" style="margin:0 0 10px;">Upload it to confirm it is exactly the file DocuMate issued. The file is only compared, never stored.</p>
            <form method="POST" action="{{ route('document.verify.file', $token) }}" enctype="multipart/form-data">
                @csrf
                <input type="file" name="pdf" accept="application/pdf" required>
                <button type="submit">Check file</button>
            </form>
            @error('pdf') <div class="flash bad">{{ $message }}</div> @enderror
            @if (session('file_check') === 'match')
                <div class="flash ok">&#10003; This file is identical to the one DocuMate issued.</div>
            @elseif (session('file_check') === 'mismatch')
                <div class="flash bad">&#10007; This file is NOT identical to the issued PDF. If you were given a re-saved or edited copy, rely on the details above and the printed original.</div>
            @endif
        </div>

        <p class="note">Verified by the Leyte Normal University VPSD DocuMate system. This page checks the university's own sealed records; it does not depend on how a signature image looks.</p>
    @endif
</div>
</body>
</html>