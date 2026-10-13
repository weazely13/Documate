<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Transaction Report {{ $ws->transaction_code }}</title>
<style>
    @page { margin: 36pt 44pt; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5pt; color: #222; line-height: 1.5; margin: 0; }
    table { border-collapse: collapse; width: 100%; }
    td, th { vertical-align: top; padding: 4pt 6pt; text-align: left; }
    h1 { font-size: 13pt; margin: 0; }
    h2 { font-size: 8pt; text-transform: uppercase; letter-spacing: .8pt; color: #2A57B4; margin: 18pt 0 5pt; border-bottom: .6pt solid #c8c8c8; padding-bottom: 3pt; }
    .muted { color: #777; }
    .mono { font-family: 'DejaVu Sans Mono', monospace; }
    .grid td { border-bottom: .4pt solid #e5e5e5; }
    .grid th { font-size: 7pt; text-transform: uppercase; color: #777; border-bottom: .8pt solid #999; }
    .k { width: 32%; color: #777; }
    .ok { color: #047857; font-weight: bold; }
    .bad { color: #b91c1c; font-weight: bold; }
</style>
</head>
<body>

<h1>Transaction Report</h1>
<div class="muted">{{ $document->name() }} &middot; {{ $document->formCode() }}</div>
<div class="muted">Generated {{ $generatedAt->format('F j, Y g:i A') }} by {{ $generatedBy }}. Confidential: for VPSD use.</div>

<h2>Summary</h2>
<table class="grid">
    <tr><td class="k">Reference</td><td class="mono"><strong>{{ $ws->transaction_code }}</strong></td></tr>
    <tr><td class="k">Status</td><td><strong>{{ $ws->workflow_status->label() }}</strong></td></tr>
    <tr><td class="k">Student</td><td>{{ $ws->field_values['student_name'] ?? '-' }} ({{ $ws->user?->student_number ?? '-' }})</td></tr>
    <tr><td class="k">Program / Year / Section</td><td>{{ $ws->field_values['student_program'] ?? '-' }}</td></tr>
    <tr><td class="k">Submitted</td><td>{{ $ws->submitted_at?->format('F j, Y g:i A') ?? '-' }}</td></tr>
    <tr><td class="k">Issued</td><td>{{ $ws->issued_at?->format('F j, Y g:i A') ?? '-' }}</td></tr>
    <tr><td class="k">Turnaround</td><td>{{ $turnaround ?? '-' }}</td></tr>
    @if ($ws->voided_at)
        <tr><td class="k">Voided</td><td>{{ $ws->voided_at->format('F j, Y g:i A') }}: {{ $ws->void_reason }}</td></tr>
    @endif
    @if ($ws->workflow_status === \App\Enums\TransactionStatus::Rejected && $ws->workflow_note)
        <tr><td class="k">Rejection reason</td><td>{{ $ws->workflow_note }}</td></tr>
    @endif
</table>

<h2>Submitted answers</h2>
<table class="grid">
    @foreach ($answers as $label => $value)
        <tr><td class="k">{{ $label }}</td><td>{!! nl2br(e($value)) !!}</td></tr>
    @endforeach
</table>

<h2>Approval chain</h2>
<table class="grid">
    <tr><th>#</th><th>Step</th><th>Signer</th><th>Status</th><th>Opened</th><th>Acted</th><th>Method</th><th>Seal</th></tr>
    @forelse ($approvals as $a)
        <tr>
            <td>{{ $a->step_order }}</td>
            <td>{{ $a->label }}<div class="muted">{{ $a->signer_type === 'external' ? 'External' : ($a->role->label ?? '') }}</div></td>
            <td>{{ $a->signer_name ?: ($a->external_name ?: '-') }}</td>
            <td>{{ ucfirst($a->status->value) }}@if ($a->reason)<div class="muted">{{ $a->reason }}</div>@endif</td>
            <td>{{ $a->opened_at?->format('M j, g:i A') ?? '-' }}</td>
            <td>{{ $a->acted_at?->format('M j, g:i A') ?? '-' }}</td>
            <td>{{ $a->auth_method ? str_replace('_', ' ', $a->auth_method) : '-' }}</td>
            <td class="mono">{{ \App\Services\Workflow\Seal::shortSeal($a->seal) ?: '-' }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="muted">No signing steps were created.</td></tr>
    @endforelse
</table>

@if ($seal)
    <h2>Integrity checks</h2>
    <table class="grid">
        <tr><td class="k">Overall</td><td class="{{ $seal['valid'] ? 'ok' : 'bad' }}">{{ $seal['valid'] ? 'Authentic and unaltered' : 'FAILED: do not rely on this record' }}</td></tr>
        <tr><td class="k">Content fingerprint</td><td class="mono">{{ $seal['fingerprint'] }}</td></tr>
        <tr><td class="k">Content matches seal</td><td class="{{ $seal['content_ok'] ? 'ok' : 'bad' }}">{{ $seal['content_ok'] ? 'Yes' : 'No' }}</td></tr>
        <tr><td class="k">Signature seals intact</td><td class="{{ $seal['signers_ok'] ? 'ok' : 'bad' }}">{{ $seal['signers_ok'] ? 'Yes' : 'No' }}</td></tr>
        <tr><td class="k">Audit chain intact</td><td class="{{ $seal['audit_ok'] ? 'ok' : 'bad' }}">{{ $seal['audit_ok'] ? 'Yes' : 'No' }}</td></tr>
        @if ($ws->pdf_sha256)
            <tr><td class="k">Issued PDF SHA-256</td><td class="mono" style="font-size:7pt;">{{ $ws->pdf_sha256 }}</td></tr>
        @endif
    </table>
@endif

<h2>Audit trail</h2>
<table class="grid">
    <tr><th style="width:22%;">When</th><th>Event</th><th style="width:26%;">By</th></tr>
    @foreach ($audit as $row)
        <tr>
            <td>{{ $row['at']->format('M j, Y g:i:s A') }}</td>
            <td>{{ $row['text'] }}@if ($row['note'])<div class="muted">{{ $row['note'] }}</div>@endif</td>
            <td>{{ $row['who'] }}@if ($row['role'])<div class="muted">{{ $row['role'] }}</div>@endif</td>
        </tr>
    @endforeach
</table>

</body>
</html>