{{-- Needs: $approvals (TransactionApproval collection with role, actor). Handles internal and external signers. --}}
<ol class="space-y-4">
    @foreach ($approvals as $a)
        @php
            $v = $a->status->value;
            $external = ($a->signer_type ?? 'internal') === 'external';
            $dot = match ($v) {
                'approved' => 'bg-emerald-500',
                'pending' => 'bg-blue-500 animate-pulse',
                'rejected' => 'bg-rose-500',
                default => 'bg-slate-300',
            };
            $name = $a->signer_name ?: ($a->actor ? $a->actor->full_name : ($external ? $a->external_name : null));
            $roleLabel = $external ? 'External signer' : ($a->role->label ?? ($a->signer_user_id ? 'Account signer' : '-'));
            $text = match ($v) {
                'approved' => 'Signed by ' . ($name ?: 'unknown') . ($a->acted_at ? ', ' . $a->acted_at->format('M j, Y g:i A') : ''),
                'pending' => 'Waiting for ' . ($external ? ($a->external_name ?: 'the rater') : ($a->role->label ?? 'signatory')),
                'rejected' => 'Declined by ' . ($name ?: 'unknown') . ($a->acted_at ? ', ' . $a->acted_at->format('M j, Y g:i A') : ''),
                'skipped' => 'Cancelled',
                default => 'Not started',
            };
        @endphp
        <li class="flex items-start gap-3" wire:key="chain-{{ $a->id }}">
            <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}"></span>
            <div class="min-w-0">
                <p class="text-sm font-medium text-slate-800">{{ $a->label }} <span class="font-normal text-slate-400">({{ $roleLabel }})</span></p>
                <p class="text-xs text-slate-500">{{ $text }}</p>

                @if ($v === 'approved' && ! empty($a->response['rating']))
                    <p class="mt-0.5 text-xs font-semibold text-slate-700">Rating: {{ $a->response['rating'] }} ({{ $a->response['rating_label'] ?? '' }})</p>
                @endif

                @if ($external)
                    <p class="text-xs text-slate-400">{{ $a->external_email }}{{ $v === 'approved' ? ' · identity confirmed with an emailed one-time code' : '' }}</p>
                @endif

                @if ($v === 'rejected' && $a->reason)
                    <p class="mt-1 text-xs text-rose-600">{{ $a->reason }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>