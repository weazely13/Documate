@php
    $s = $ws->workflow_status->value;
    $hasApprovals = $approvals->isNotEmpty();
    $anyApproved = $approvals->contains(fn ($a) => $a->status->value === 'approved');
    $btn = 'rounded-xl px-4 py-2.5 text-sm font-semibold transition disabled:opacity-50';
    $primary = $btn . ' bg-[#2A57B4] text-white hover:bg-[#24499A]';
    $secondary = $btn . ' border border-slate-300 bg-white text-slate-700 hover:bg-slate-50';
    $danger = $btn . ' border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100';
    $noHolder = $chainPreview->contains(fn ($c) => empty($c['holders']));
@endphp

<div class="mx-auto max-w-6xl space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('admin.workflow.index') }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; Workflow Review</a>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $document->name() }}</h2>
            <p class="font-mono text-xs text-slate-400">{{ $ws->transaction_code }}</p>
        </div>
        @include('livewire.workflow._badge', ['status' => $ws->workflow_status])
    </div>

    @if ($notice)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ $notice }}</div>
    @endif
    @if (! $modal)
        @error('action')
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
        @enderror
    @endif

    {{-- What to do next --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        @switch($s)
            @case('submitted')
                <p class="text-sm font-semibold text-slate-900">New submission</p>
                <p class="mt-0.5 text-sm text-slate-500">Read the answers below, then start the review.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button type="button" wire:click="startReview" wire:loading.attr="disabled" class="{{ $primary }}">Start review</button>
                    <button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject</button>
                </div>
                @break

            @case('under_review')
                @if (! $hasApprovals)
                    <p class="text-sm font-semibold text-slate-900">Under review</p>
                    <p class="mt-0.5 text-sm text-slate-500">If the answers are in order, send it for signing. The approval chain below will open.</p>

                    <ol class="mt-4 space-y-2">
                        @foreach ($chainPreview as $i => $c)
                            <li class="flex items-start gap-3 rounded-xl bg-slate-50 px-3.5 py-2.5" wire:key="cp-{{ $i }}">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">{{ $i + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800">{{ $c['label'] }}: {{ $c['role'] }}</p>
                                    @if ($c['holders'])
                                        <p class="text-xs text-slate-500">{{ implode(', ', $c['holders']) }}</p>
                                    @else
                                        <p class="text-xs font-medium text-amber-600">No one holds this role right now. Assign someone under Manage Users &gt; Signatories first.</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    @if ($reviewerFields)
                        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">Fill in before sending</p>
                            <p class="mt-0.5 text-xs text-slate-500">These are completed by the VPSD office and printed on the document.</p>
                            <div class="mt-3 space-y-3">
                                @foreach ($reviewerFields as $name => $f)
                                    <div wire:key="rf-{{ $name }}">
                                        <label class="block text-xs font-semibold text-slate-600">
                                            {{ $f['label'] }} @if ($f['required'] ?? false)<span class="text-rose-500">*</span>@endif
                                        </label>
                                        <input type="{{ ['date' => 'date', 'time' => 'time'][$f['type']] ?? 'text' }}"
                                            wire:model="reviewerValues.{{ $name }}"
                                            @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                            class="mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($needsIdentityCheck)
                        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-900">Check the rater before sending</p>
                            <p class="mt-0.5 text-xs text-amber-800">
                                Confirm this person is real and agreed to rate the student. Use contact details from official records, not the ones the student typed.
                            </p>

                            <label class="mt-3 block text-xs font-semibold text-amber-900">How did you verify them?</label>
                            <select wire:model.live="identityMethod" class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm">
                                <option value="">Choose…</option>
                                @foreach ($identityMethods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('identityMethod') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

                            <label class="mt-3 block text-xs font-semibold text-amber-900">Note (optional)</label>
                            <input type="text" wire:model="identityNote" maxlength="300" placeholder="e.g. Confirmed with HR, ext. 214"
                                class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm">
                            <p class="mt-1 text-[11px] text-amber-700">Don't write ID numbers or personal phone numbers here.</p>

                            <label class="mt-3 flex items-start gap-2 text-sm text-amber-900">
                                <input type="checkbox" wire:model.live="identityConfirmed" class="mt-0.5 h-4 w-4 accent-[#2A57B4]">
                                <span>I confirm this person's identity and that they agreed to rate this student.</span>
                            </label>
                            @error('identityConfirmed') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button type="button" wire:click="sendForSigning" wire:loading.attr="disabled" @disabled($noHolder || ($needsIdentityCheck && (! $identityMethod || ! $identityConfirmed))) class="{{ $primary }}">Send for signing</button>
                        <button type="button" wire:click="openModal('request')" class="{{ $secondary }}">Request changes</button>
                        <button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject</button>
                    </div>
                @else
                    @php $open = $approvals->first(fn ($a) => $a->status->value === 'pending'); @endphp
                    <p class="text-sm font-semibold text-slate-900">Out for signing</p>
                    <p class="mt-0.5 text-sm text-slate-500">
                        @if ($open) Waiting for {{ $open->role->label ?? 'the signatory' }}. @endif
                        @if ($anyApproved) Changes can no longer be requested because someone has signed. @endif
                    </p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if ($open && $open->signer_type === 'external' && ! $document->manualExternalInvites())
                            <button type="button" wire:click="resendInvite" wire:loading.attr="disabled" class="{{ $secondary }}">Resend invitation</button>
                        @endif
                        @unless ($anyApproved)
                            <button type="button" wire:click="openModal('request')" class="{{ $secondary }}">Request changes</button>
                        @endunless
                        <button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject</button>
                    </div>
                @endif
                @break

            @case('action_required')
                <p class="text-sm font-semibold text-slate-900">Waiting on the student</p>
                <p class="mt-0.5 text-sm text-slate-500">You asked: {{ $ws->workflow_note }}</p>
                <div class="mt-4"><button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject instead</button></div>
                @break

            @case('approved')
                <p class="text-sm font-semibold text-slate-900">Final verification</p>
                <p class="mt-0.5 text-sm text-slate-500">Confirm each point, then complete. This seals the record and issues the PDF. It cannot be undone.</p>
                <ul class="mt-3 space-y-1.5 text-sm text-slate-700">
                    <li class="flex gap-2"><i class='bx bx-check-circle text-lg text-emerald-600'></i> All {{ $approvals->count() }} signatures collected</li>
                    <li class="flex gap-2"><i class='bx bx-check-circle text-lg text-emerald-600'></i> Answers were reviewed and accepted</li>
                    <li class="flex gap-2"><i class='bx bx-check-circle text-lg text-emerald-600'></i> Nothing is waiting on the student</li>
                </ul>
                <div class="mt-4">
                    <button type="button" wire:click="complete" wire:loading.attr="disabled"
                            wire:confirm="Complete this transaction? The record will be sealed and can no longer change."
                            class="{{ $primary }}">Complete and issue</button>
                </div>
                @break

            @case('completed')
                <p class="text-sm font-semibold text-emerald-700">Completed {{ $ws->completed_at?->format('M j, Y g:i A') }}</p>
                <div class="mt-3">
                    <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" class="{{ $primary }}">Download official PDF</button>
                </div>
                <button type="button" wire:click="openModal('void')" class="{{ $danger }}">Void this document</button>
                @break

            @case('rejected')
                <p class="text-sm font-semibold text-rose-700">Rejected</p>
                <p class="mt-0.5 text-sm text-slate-600">{{ $ws->workflow_note }}</p>
                @break
            @case('voided')
                <p class="text-sm font-semibold text-slate-700">Voided {{ $ws->voided_at?->format('M j, Y g:i A') }}</p>
                <p class="mt-0.5 text-sm text-slate-600">{{ $ws->void_reason }}</p>
                @if ($replacementCode)
                    <p class="mt-2 text-xs text-slate-500">Corrected draft: <span class="font-mono font-semibold">{{ $replacementCode }}</span>. It appears here once the student resubmits it.</p>
                @endif
                @break
        @endswitch
    </section>
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-5">
            @if (! empty($ws->field_values['item_photo_path']))
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Item photo</h3>
                    <p class="text-xs text-slate-400">For identification only. Not part of the PDF.</p>
                    <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}" alt="Item photo"
                        class="mt-3 max-h-72 rounded-xl border border-slate-200 bg-white object-contain p-1">
                </section>
            @endif
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Document preview</h3>
                <p class="text-xs text-slate-400">This is exactly what the official PDF will contain.</p>
                <div class="mt-3">
                    @include('livewire.workflow._preview', [
                        'src' => route('admin.workflow.preview', $ws->workspace_id),
                        'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
                    ])
                </div>
            </section>

            @if ($hasApprovals)
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Signatures</h3>
                    <div class="mt-3">@include('livewire.workflow._chain', ['approvals' => $approvals])</div>
                </section>
            @endif

            @if ($seal)
                <section class="rounded-2xl border p-5 shadow-sm {{ $seal['valid'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                    <h3 class="text-sm font-semibold {{ $seal['valid'] ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $seal['valid'] ? 'Seal intact' : 'Seal check failed' }}
                    </h3>
                    <p class="mt-0.5 text-xs {{ $seal['valid'] ? 'text-emerald-700' : 'text-rose-700' }}">Fingerprint {{ $seal['fingerprint'] }}. Rechecked from the database every time you open this page.</p>
                    <ul class="mt-3 space-y-1 text-sm text-slate-700">
                        @foreach (['content_ok' => 'Answers match the sealed fingerprint', 'signers_ok' => 'Every signature seal and image is intact', 'audit_ok' => 'Activity history was not altered'] as $k => $label)
                            <li class="flex items-center gap-2"><i class='bx {{ $seal[$k] ? "bx-check-circle text-emerald-600" : "bx-x-circle text-rose-600" }} text-lg'></i> {{ $label }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" class="mt-3 inline-block text-xs font-semibold text-[#2A57B4] hover:underline">Open the public verification page</a>
                </section>
            @endif
        </div>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Activity</h3>
            <p class="text-xs text-slate-400">Newest first</p>
            <div class="mt-4 space-y-4">
                @foreach ($audit as $i => $item)
                    <div class="relative pl-5" wire:key="audit-{{ $i }}">
                        <span class="absolute left-0 top-1.5 h-2 w-2 rounded-full bg-slate-300"></span>
                        <p class="text-sm font-medium text-slate-800">{{ $item['text'] }}</p>
                        <p class="text-xs text-slate-500">{{ $item['who'] }}{{ $item['role'] ? ' (' . $item['role'] . ')' : '' }}</p>
                        <p class="text-xs text-slate-400">{{ $item['at']->format('M j, Y g:i A') }}</p>
                        @if ($item['note']) <p class="mt-0.5 text-xs text-slate-500">{{ $item['note'] }}</p> @endif
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
    <a href="{{ route('admin.workflow.report', $ws->workspace_id) }}"
    class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
        Download report
    </a>

    {{-- Request changes / Reject --}}
    @if ($modal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" wire:keydown.escape="closeModal">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-bold text-slate-900">
                    {{ ['request' => 'Request changes', 'reject' => 'Reject this transaction', 'void' => 'Void this document'][$modal] }}
                </h3>
               <p class="mt-1 text-sm text-slate-500">
                    @if ($modal === 'request') The student sees this message and can edit and resubmit.
                    @elseif ($modal === 'reject') The student sees this reason. Rejection is final; they would need to start a new transaction.
                    @else The document will show as VOIDED on the verification page and can no longer be downloaded. The student sees this reason.
                    @endif
                </p>
                <textarea wire:model="message" rows="4" maxlength="500"
                    placeholder="{{ ['request' => 'What needs to change?', 'reject' => 'Why is it being rejected?', 'void' => 'What is wrong with the issued document?'][$modal] ?? '' }}"
                    class="mt-4 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100"></textarea>
                @if ($modal === 'void')
                    <label class="mt-3 flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="reissue" class="mt-0.5 h-4 w-4 accent-[#2A57B4]">
                        <span>Also create a corrected draft for the student (their answers are copied; they fix and resubmit; it needs review and signatures again).</span>
                    </label>
                @endif
                        
                @error('action') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" wire:click="closeModal" class="{{ $secondary }}">Cancel</button>
                    <button type="button" wire:click="submitModal" wire:loading.attr="disabled"
                            class="{{ in_array($modal, ['reject', 'void']) ? $btn . ' bg-rose-600 text-white hover:bg-rose-700' : $primary }}">
                            {{ ['request' => 'Send request', 'reject' => 'Reject transaction', 'void' => 'Void document'][$modal] }}    
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
