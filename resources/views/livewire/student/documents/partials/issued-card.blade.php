@php
    use App\Workflow\Documents\DocumentRegistry;
    use Illuminate\Support\Str;

    $nd = DocumentRegistry::get($workspace->document_type);
    $nv = $workspace->field_values ?? [];
    $rose = $nd->noticeTone() === 'rose';
    $status = $workspace->workflow_status?->value;
    $done = $status === 'completed';
    $closed = in_array($status, ['rejected', 'voided'], true);

    $d = \Carbon\Carbon::parse($nd->noticeDate($nv) ?? $workspace->submitted_at ?? $workspace->created_at);
    if ($done && $workspace->issued_at) {
        $d = $workspace->issued_at;
    }

    [$statusLabel, $statusClass] = match (true) {
        $done => ['Completed', 'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        $status === 'approved' => ['Signed, final check pending', 'bg-teal-50 text-teal-700 ring-teal-200'],
        $status === 'under_review' && $workspace->approvals_count > 0 => ['Awaiting signature', 'bg-amber-50 text-amber-700 ring-amber-200'],
        $status === 'under_review' => ['Under review', 'bg-indigo-50 text-indigo-700 ring-indigo-200'],
        $status === 'rejected' => ['Rejected', 'bg-rose-50 text-rose-700 ring-rose-200'],
        $status === 'voided' => ['Voided', 'bg-slate-200 text-slate-600 ring-slate-300'],
        default => [$workspace->workflow_status?->label() ?? 'Issued', 'bg-slate-100 text-slate-600 ring-slate-200'],
    };

    $border = $done ? 'border-emerald-200' : ($closed ? 'border-slate-200' : ($rose ? 'border-rose-200' : 'border-blue-200'));
    $dateBg = $done ? 'bg-emerald-600' : ($closed ? 'bg-slate-400' : ($rose ? 'bg-rose-600' : 'bg-[#2A57B4]'));
    $titleColor = $done ? 'text-emerald-700' : ($closed ? 'text-slate-500' : ($rose ? 'text-rose-700' : 'text-[#2A57B4]'));

    $line = match (true) {
        $done => 'Completed' . ($workspace->issued_at ? ' on ' . $workspace->issued_at->format('F j, Y') : '') . '.',
        $status === 'rejected' => $workspace->workflow_note ? Str::limit($workspace->workflow_note, 90) : 'This document was rejected.',
        $status === 'voided' => 'Withdrawn by the VPSD office.',
        default => $nd->noticeLine($nv) ?: $nd->name(),
    };
@endphp

<a href="{{ route('student.transactions.show', $workspace->workspace_id) }}" wire:navigate
   wire:key="issued-{{ $workspace->workspace_id }}"
   class="relative flex items-start gap-3 rounded-2xl border bg-white p-4 shadow-lg transition hover:shadow-xl {{ $border }} {{ $closed ? 'opacity-75' : '' }}">
    <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-xl text-white shadow-sm {{ $dateBg }}">
        <span class="text-[9px] font-bold uppercase opacity-80">{{ $d->format('M') }}</span>
        <span class="text-lg font-extrabold leading-none">{{ $d->format('d') }}</span>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm font-bold {{ $titleColor }}">{{ $nd->noticeTitle($nv) }}</p>
            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
        <p class="mt-0.5 text-xs leading-snug text-slate-600">{{ $line }}</p>
        <p class="mt-2 font-mono text-[10px] text-slate-400">
            {{ $workspace->transaction_code }} &middot; {{ $nd->name() }}
            @if (! $done && ! $closed && $workspace->approvals_count > 0)
                &middot; {{ $workspace->approved_count }}/{{ $workspace->approvals_count }} signed
            @endif
            &middot; issued {{ ($workspace->submitted_at ?? $workspace->created_at)->diffForHumans() }}
        </p>
    </div>
</a>