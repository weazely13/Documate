@php
    $cls = match ($status?->value) {
        'draft' => 'bg-slate-100 text-slate-600 ring-slate-200',
        'submitted' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'under_review' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'action_required' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'approved' => 'bg-teal-50 text-teal-700 ring-teal-200',
        'completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'voided' => 'bg-slate-200 text-slate-600 ring-slate-300',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
@endphp
<span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $cls }}">{{ $status?->label() ?? '-' }}</span>
