<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify a Document · DocuMate</title>
    <link rel="icon" href="{{ asset('images/favicon.png') }}" type="image/png">
    <style>
        :root { --blue:#2A57B4; --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; background:#f8fafc; color:var(--ink); }
        .wrap { max-width: 520px; margin: 0 auto; padding: 32px 16px 48px; }
        .brand { font-size: 13px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--blue); }
        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:24px; margin-top:16px; box-shadow:0 1px 2px rgba(0,0,0,.04); }
        h1 { margin:0 0 6px; font-size:22px; }
        p { margin:0; font-size:14px; line-height:1.55; color:var(--muted); }
        label { display:block; margin-top:16px; font-size:13px; font-weight:600; }
        input { width:100%; margin-top:6px; padding:11px 12px; border:1px solid #cbd5e1; border-radius:10px; font-size:15px; font-family: ui-monospace, Menlo, monospace; text-transform: uppercase; }
        input:focus { outline:none; border-color:var(--blue); box-shadow:0 0 0 4px rgba(42,87,180,.12); }
        small { display:block; margin-top:4px; font-size:12px; color:var(--muted); }
        button { margin-top:20px; width:100%; background:var(--blue); color:#fff; border:0; border-radius:10px; padding:12px; font-size:15px; font-weight:600; cursor:pointer; }
        .err { margin-top:14px; padding:10px 12px; border-radius:10px; background:#fef2f2; border:1px solid #fecaca; color:#991b1b; font-size:14px; }
        .note { margin-top:16px; font-size:12px; line-height:1.5; color:var(--muted); }
    </style>
</head>
<body>
<div class="wrap">
    <div class="brand">DocuMate · VPSD Document Verification</div>

    <div class="card">
        <h1>Verify a document</h1>
        <p>Check that a printed or PDF copy was really issued by the VPSD office. You can also scan the QR code printed at the bottom of the document.</p>

        @if ($errors->any())
            <div class="err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('document.lookup.find') }}">
            @csrf
            <label for="reference">Reference No.</label>
            <input id="reference" name="reference" value="{{ old('reference') }}" placeholder="VPSD-2026-00125" required maxlength="32" autocomplete="off">

            <label for="fingerprint">Fingerprint</label>
            <input id="fingerprint" name="fingerprint" value="{{ old('fingerprint') }}" placeholder="A1B2-C3D4-E5F6-0718" required maxlength="32" autocomplete="off">
            <small>Both are printed at the bottom of the document.</small>

            <button type="submit">Check document</button>
        </form>
    </div>

    <p class="note">Verification only confirms the university's own sealed record. No personal data is shown unless both the Reference No. and Fingerprint match.</p>
</div>
</body>
</html>