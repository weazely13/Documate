{{-- A transaction's details as a property list (Workflow Review panel and the full record).
     Needs: $ws (with user.program), $doc, $status ([text, text classes, icon classes]). Optional: $issuer, $waiting (WaitingTime::describe). --}}
@php
    $student = $ws->user;
    $rows = array_filter([
        'Document' => $doc?->name() ?? $ws->document_type,
        'Code' => $ws->transaction_code,
        'Student number' => $student?->student_number,
        'Program' => trim(($student?->program?->name ?? '') . ($student?->year_level ? ' · Year ' . $student->year_level : '')) ?: null,
        'Email' => $student?->email,
        'Issued by' => $issuer ?? null,
        'Submitted' => $ws->submitted_at?->format('M j, Y, g:i A'),
        'Completed' => $ws->completed_at?->format('M j, Y, g:i A'),
    ], fn ($v) => filled($v));

    $waitClass = match ($waiting['level'] ?? null) {
        'overdue' => '-ml-2 rounded-md bg-red-50 px-2 py-0.5 font-medium text-red-800',
        'urgent' => 'font-medium text-red-700',
        'follow_up' => 'font-medium text-orange-700',
        'due' => 'text-slate-700',
        default => 'text-slate-600',
    };
    $row = 'grid grid-cols-[minmax(96px,120px)_minmax(0,1fr)] items-baseline gap-3 py-2';
@endphp
<dl class="divide-y divide-slate-100 text-sm">
    <div class="{{ $row }}">
        <dt class="text-slate-500">Status</dt>
        <dd class="min-w-0">
            <span class="inline-flex items-center gap-1.5 {{ $status[1] }}">
                <i class="bx {{ $status[2] }} text-base" aria-hidden="true"></i>{{ $status[0] }}
            </span>
        </dd>
    </div>
    @foreach ($rows as $label => $value)
        <div class="{{ $row }}">
            <dt class="text-slate-500">{{ $label }}</dt>
            <dd class="min-w-0 break-words text-slate-900 {{ $label === 'Code' ? 'tabular-nums' : '' }}">{{ $value }}</dd>
        </div>
    @endforeach
    @if (! empty($waiting))
        <div class="{{ $row }}">
            <dt class="text-slate-500">Waiting</dt>
            <dd>
                <span class="inline-flex items-center gap-2 {{ $waitClass }}">
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $waiting['dot'] }}" aria-hidden="true"></span>
                    {{ $waiting['text'] }}
                </span>
            </dd>
        </div>
    @endif
</dl>
