@php
    $s = $ws->workflow_status->value;
    $hasApprovals = $approvals->isNotEmpty();
    $anyApproved = $approvals->contains(fn ($a) => $a->status->value === 'approved');
    $noHolder = $chainPreview->contains(fn ($c) => empty($c['holders']));

    // Same building blocks as Workflow Review (DESIGN.md: 8px buttons, 36px / 44px on touch, 14px/500 labels).
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $btn = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $primary = $btn . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $secondary = $btn . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $danger = $btn . ' border border-rose-200 bg-white text-rose-700 hover:bg-rose-50';

    $card = 'rounded-2xl border border-slate-200 bg-white p-4 sm:p-5';
    $actions = 'mt-4 grid gap-2 sm:flex sm:flex-wrap';
    $input = 'mt-1 w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:py-2 sm:text-sm';

    $who = $ws->field_values['student_name'] ?? ($ws->user?->full_name ?? '');
    $parts = preg_split('/\s+/', trim($who)) ?: [];
    $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '')) ?: '?';
    $avatar = $ws->user?->profile_picture ? asset('storage/' . $ws->user->profile_picture) : null;

    $previewSrc = route('admin.workflow.preview', $ws->workspace_id);
    $previewVersion = $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count();
    $aiApplies = in_array($s, ['submitted', 'under_review', 'action_required', 'approved'], true)
        && ! $document->issuedByOffice() && ! $document->issuerIsStaff();
@endphp

<div class="mx-auto max-w-[1280px] space-y-5">

    {{-- Header: who and what, and the way back (the topbar already shows where you are) --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <span class="relative grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-sm font-semibold text-[#1E4090]" aria-hidden="true">
                {{ $initials }}
                @if ($avatar)
                    <img src="{{ $avatar }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                @endif
            </span>
            <div class="min-w-0">
                <h1 class="break-words text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">{{ $who ?: $ws->transaction_code }}</h1>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $document->name() }} · <span class="tabular-nums">{{ $ws->transaction_code }}</span>
                    @if ($ws->updated_at) <span class="whitespace-nowrap">· last updated {{ $ws->updated_at->format('M j, g:i A') }}</span> @endif
                </p>
            </div>
        </div>
        <a href="{{ $this->backUrl }}" wire:navigate class="{{ $secondary }} shrink-0 self-start"><i class="bx bx-arrow-back text-base text-slate-500" aria-hidden="true"></i> Back to {{ strtolower($this->backLabel) }}</a>
    </div>

    @if ($notice)
        <p class="flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status" wire:key="notice-{{ md5($notice) }}">
            <i class="bx bx-check-circle mt-0.5 text-base text-emerald-600" aria-hidden="true"></i> {{ $notice }}
        </p>
    @endif
    @if (! $modal && $errors->has('action'))
        <p class="flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900" role="alert" wire:key="error-{{ md5($errors->first('action')) }}">
            <i class="bx bx-error-circle mt-0.5 text-base text-rose-600" aria-hidden="true"></i> {{ $errors->first('action') }}
        </p>
    @endif

    {{-- Phones: one column in reading order (the two wrappers dissolve and order-* sets the sequence).
         Large screens: details and the document on the left; decide, AI check, then checkpoints on the right. --}}
    <div class="flex flex-col gap-5 lg:grid lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start">

        <div class="contents lg:block lg:min-w-0 lg:space-y-5">

            <section class="{{ $card }} order-1" aria-labelledby="rec-details">
                <h2 id="rec-details" class="sr-only">Details</h2>
                @include('livewire.admin.workflow._record-props', ['ws' => $ws, 'doc' => $document, 'status' => $statusLine, 'issuer' => $issuer, 'waiting' => $waiting])
            </section>



            {{-- The document itself --}}
            <section class="{{ $card }} order-4" aria-labelledby="rec-preview">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 id="rec-preview" class="text-[15px] font-semibold text-slate-900">Document preview</h2>
                        <p class="text-xs text-slate-500">A preview of the layout. The downloaded PDF is the official copy, so spacing may differ slightly.</p>
                    </div>
                    <button type="button" @click="$dispatch('open-doc-preview')"
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}"
                            aria-label="Open the preview larger" title="Open larger">
                        <i class="bx bx-expand-alt text-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="mt-3">
                    @include('livewire.workflow._preview', ['src' => $previewSrc, 'version' => $previewVersion])
                </div>
            </section>

            @if (! empty($ws->field_values['item_photo_path']))
                <section class="{{ $card }} order-5" aria-labelledby="rec-photo">
                    <h2 id="rec-photo" class="text-[15px] font-semibold text-slate-900">Item photo</h2>
                    <p class="text-xs text-slate-500">For identification only. Not part of the PDF.</p>
                    <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}" alt="Photo of the item reported in {{ $ws->transaction_code }}"
                         class="mt-3 max-h-72 max-w-full rounded-lg border border-slate-200 bg-white object-contain p-1">
                </section>
            @endif

            @if ($seal)
                <section class="order-6 rounded-2xl border p-4 sm:p-5 {{ $seal['valid'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}" aria-labelledby="rec-seal">
                    <h2 id="rec-seal" class="text-[15px] font-semibold {{ $seal['valid'] ? 'text-emerald-900' : 'text-rose-900' }}">
                        {{ $seal['valid'] ? 'Seal intact' : 'Seal check failed' }}
                    </h2>
                    <p class="mt-0.5 break-all text-xs {{ $seal['valid'] ? 'text-emerald-800' : 'text-rose-800' }}">Fingerprint {{ $seal['fingerprint'] }}. Rechecked from the database every time you open this page.</p>
                    <ul class="mt-3 space-y-1.5 text-sm text-slate-800">
                        @foreach (['content_ok' => 'Answers match the sealed fingerprint', 'signers_ok' => 'Every signature seal and image is intact', 'audit_ok' => 'Activity history was not altered'] as $k => $label)
                            <li class="flex items-center gap-2">
                                <span class="h-2 w-2 shrink-0 rounded-full {{ $seal[$k] ? 'bg-emerald-600' : 'bg-red-600' }}" aria-hidden="true"></span>
                                {{ $label }}
                                @unless ($seal[$k]) <span class="text-xs font-medium text-rose-700">Failed</span> @endunless
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" rel="noopener"
                       class="mt-3 inline-flex items-center gap-1 rounded text-sm font-medium text-[#2A57B4] hover:underline {{ $focus }}">
                        Open the public verification page <i class="bx bx-link-external text-sm" aria-hidden="true"></i>
                    </a>
                </section>
            @endif
        </div>

        {{-- The admin's side: decide first, then the AI check, then signatures and checkpoints --}}
        <div class="contents lg:block lg:space-y-5">
                {{-- What to do next --}}
                <section class="{{ $card }} order-2" aria-labelledby="next-step">
                    @switch($s)
                        @case('submitted')
                            <h2 id="next-step" class="text-[15px] font-semibold text-slate-900">New submission</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Read the answers in the preview, then start the review.</p>
                            <div class="{{ $actions }}">
                                <button type="button" wire:click="startReview" wire:loading.attr="disabled" class="{{ $primary }}">Start review</button>
                                <button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject</button>
                            </div>
                            @break

                        @case('under_review')
                            @if (! $hasApprovals)
                                <h2 id="next-step" class="text-[15px] font-semibold text-slate-900">Under review</h2>
                                <p class="mt-0.5 text-sm text-slate-500">If the answers are in order, send it for signing. The signing order below opens.</p>

                                <ol class="mt-4 space-y-2">
                                    @foreach ($chainPreview as $i => $c)
                                        <li class="flex items-start gap-3 rounded-xl border border-slate-200 px-3.5 py-2.5" wire:key="cp-{{ $i }}">
                                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-[#e2e9f7] text-[11px] font-semibold text-[#1E4090]" aria-hidden="true">{{ $i + 1 }}</span>
                                            <div class="min-w-0">
                                                <p class="break-words text-sm font-medium text-slate-900"><span class="sr-only">Step {{ $i + 1 }}: </span>{{ $c['label'] }}: {{ $c['role'] }}</p>
                                                @if ($c['holders'])
                                                    <p class="break-words text-xs text-slate-500">{{ implode(', ', $c['holders']) }}</p>
                                                @else
                                                    <p class="text-xs font-medium text-amber-700">No one holds this role right now. Assign someone under Manage Users &gt; Signatories first.</p>
                                                @endif
                                            </div>
                                        </li>
                                    @endforeach
                                </ol>

                                @if ($reviewerFields)
                                    <fieldset class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                        <legend class="px-1 text-sm font-semibold text-slate-900">Fill in before sending</legend>
                                        <p class="text-xs text-slate-500">These are completed by the VPSD office and printed on the document.</p>
                                        <div class="mt-3 space-y-3">
                                            @foreach ($reviewerFields as $name => $f)
                                                <div wire:key="rf-{{ $name }}">
                                                    <label for="rf-{{ $name }}" class="block text-sm font-medium text-slate-800">
                                                        {{ $f['label'] }} @if ($f['required'] ?? false)<span class="text-rose-600" aria-hidden="true">*</span><span class="sr-only">(required)</span>@endif
                                                    </label>
                                                    <input id="rf-{{ $name }}" type="{{ ['date' => 'date', 'time' => 'time'][$f['type']] ?? 'text' }}"
                                                           wire:model="reviewerValues.{{ $name }}"
                                                           @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                                           @if ($f['required'] ?? false) required @endif
                                                           class="{{ $input }}">
                                                </div>
                                            @endforeach
                                        </div>
                                    </fieldset>
                                @endif

                                @if ($needsIdentityCheck)
                                    <fieldset class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                        <legend class="px-1 text-sm font-semibold text-amber-900">Check the rater before sending</legend>
                                        <p class="text-xs text-amber-900">
                                            Confirm this person is real and agreed to rate the student. Use contact details from official records, not the ones the student typed.
                                        </p>

                                        <label for="identity-method" class="mt-3 block text-sm font-medium text-amber-900">How did you verify them?</label>
                                        <select id="identity-method" wire:model.live="identityMethod" class="{{ $input }} border-amber-300">
                                            <option value="">Choose…</option>
                                            @foreach ($identityMethods as $key => $label)
                                                <option value="{{ $key }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('identityMethod') <p class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</p> @enderror

                                        <label for="identity-note" class="mt-3 block text-sm font-medium text-amber-900">Note (optional)</label>
                                        <input id="identity-note" type="text" wire:model="identityNote" maxlength="300" placeholder="e.g. Confirmed with HR, ext. 214"
                                               aria-describedby="identity-note-hint" class="{{ $input }} border-amber-300">
                                        <p id="identity-note-hint" class="mt-1 text-xs text-amber-800">Don't write ID numbers or personal phone numbers here.</p>

                                        <label class="mt-3 flex items-start gap-2 text-sm text-amber-900">
                                            <input type="checkbox" wire:model.live="identityConfirmed" class="mt-0.5 h-4 w-4 rounded border-amber-400 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                                            <span>I confirm this person's identity and that they agreed to rate this student.</span>
                                        </label>
                                        @error('identityConfirmed') <p class="mt-1 text-xs font-medium text-rose-700">{{ $message }}</p> @enderror
                                    </fieldset>
                                @endif

                                <div class="{{ $actions }}">
                                    <button type="button" wire:click="sendForSigning" wire:loading.attr="disabled"
                                            @disabled($noHolder || ($needsIdentityCheck && (! $identityMethod || ! $identityConfirmed)))
                                            class="{{ $primary }}">Send for signing</button>
                                    <button type="button" wire:click="openModal('request')" class="{{ $secondary }}">Request changes</button>
                                    <button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject</button>
                                </div>
                            @else
                                @php $open = $approvals->first(fn ($a) => $a->status->value === 'pending'); @endphp
                                <h2 id="next-step" class="text-[15px] font-semibold text-slate-900">Out for signing</h2>
                                <p class="mt-0.5 text-sm text-slate-500">
                                    @if ($open) Waiting for {{ $open->role->label ?? ($open->external_name ?: 'the signatory') }}. @endif
                                    @if ($anyApproved) Changes can no longer be requested because someone has signed. @endif
                                </p>
                                <div class="{{ $actions }}">
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
                            <h2 id="next-step" class="text-[15px] font-semibold text-slate-900">Waiting on the student</h2>
                            <p class="mt-2 break-words rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900">You asked: {{ $ws->workflow_note }}</p>
                            <div class="{{ $actions }}">
                                <button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject instead</button>
                            </div>
                            @break

                        @case('approved')
                            <h2 id="next-step" class="text-[15px] font-semibold text-slate-900">Final verification</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Confirm each point, then complete. This seals the record and issues the PDF. It cannot be undone.</p>
                            <ul class="mt-3 space-y-1.5 text-sm text-slate-700">
                                <li class="flex items-center gap-2"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-600" aria-hidden="true"></span> All {{ $approvals->count() }} signatures collected</li>
                                <li class="flex items-center gap-2"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-600" aria-hidden="true"></span> Answers were reviewed and accepted</li>
                                <li class="flex items-center gap-2"><span class="h-2 w-2 shrink-0 rounded-full bg-emerald-600" aria-hidden="true"></span> Nothing is waiting on the student</li>
                            </ul>
                            <div class="{{ $actions }}">
                                <button type="button" wire:click="complete" wire:loading.attr="disabled"
                                        wire:confirm="Complete this transaction? The record will be sealed and can no longer change."
                                        class="{{ $primary }}">Complete and issue</button>
                            </div>
                            @break

                        @case('completed')
                            <h2 id="next-step" class="text-[15px] font-semibold text-emerald-800">Completed {{ $ws->completed_at?->format('M j, Y g:i A') }}</h2>
                            <div class="{{ $actions }}">
                                <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" class="{{ $primary }}">
                                    <i class="bx bx-download text-base" aria-hidden="true"></i> Download official PDF
                                </button>
                                <button type="button" wire:click="openModal('void')" class="{{ $danger }}">Void this document</button>
                            </div>
                            @break

                        @case('rejected')
                            <h2 id="next-step" class="text-[15px] font-semibold text-rose-800">Rejected</h2>
                            <p class="mt-0.5 break-words text-sm text-slate-700">{{ $ws->workflow_note }}</p>
                            @break

                        @case('voided')
                            <h2 id="next-step" class="text-[15px] font-semibold text-slate-800">Voided {{ $ws->voided_at?->format('M j, Y g:i A') }}</h2>
                            <p class="mt-0.5 break-words text-sm text-slate-700">{{ $ws->void_reason }}</p>
                            @if ($replacementCode)
                                <p class="mt-2 text-xs text-slate-500">Corrected draft: <span class="font-medium tabular-nums text-slate-700">{{ $replacementCode }}</span>. It appears here once the student resubmits it.</p>
                            @endif
                            @break
                    @endswitch
                </section>

                @if ($aiApplies)
                    <div class="{{ $card }} order-3">
                        @include('livewire.admin.workflow._ai-check', ['ws' => $ws, 'run' => 'runAiCheck'])
                    </div>
                @endif

            <section class="{{ $card }} order-7 space-y-6" aria-label="Signatures and checkpoints">
                @include('livewire.admin.workflow._record-timeline', [
                    'approvals' => $approvals, 'audit' => $audit, 'idPrefix' => 'rec',
                    'reportUrl' => route('admin.workflow.report', $ws->workspace_id), 'reportEvent' => 'open-report-preview',
                ])
            </section>
        </div>
    </div>

    @include('livewire.admin.workflow._preview-modal', ['src' => $previewSrc, 'version' => $previewVersion, 'title' => $document->name() . ' · ' . $ws->transaction_code])
    @include('livewire.admin.workflow._preview-modal', [
        'event' => 'open-report-preview',
        'src' => route('admin.workflow.report.preview', $ws->workspace_id),
        'version' => $previewVersion . '-' . $audit->count(),
        'title' => 'Audit trail · ' . $ws->transaction_code,
        'note' => 'Everything that happened to this transaction. Download it as a PDF to keep a copy.',
        'download' => route('admin.workflow.report', $ws->workspace_id),
    ])

    {{-- Request changes / Reject / Void --}}
    @if ($modal)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             wire:keydown.escape="closeModal" wire:click.self="closeModal"
             role="dialog" aria-modal="true" aria-labelledby="modal-title" aria-describedby="modal-lead">
            <div class="max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6"
                 x-data x-init="$nextTick(() => $refs.msg.focus())">
                <h2 id="modal-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">
                    {{ ['request' => 'Request changes', 'reject' => 'Reject this transaction', 'void' => 'Void this document'][$modal] }}
                </h2>
                <p id="modal-lead" class="mt-1 text-sm text-slate-500">
                    @if ($modal === 'request') The student sees this message and can edit and resubmit.
                    @elseif ($modal === 'reject') The student sees this reason. Rejection is final; they would need to start a new transaction.
                    @else The document will show as voided on the verification page and can no longer be downloaded. The student sees this reason.
                    @endif
                </p>

                <label for="modal-message" class="mt-4 block text-sm font-medium text-slate-800">
                    {{ ['request' => 'What needs to change?', 'reject' => 'Why is it being rejected?', 'void' => 'What is wrong with the issued document?'][$modal] }}
                    <span class="text-rose-600" aria-hidden="true">*</span>
                </label>
                <textarea id="modal-message" x-ref="msg" wire:model="message" rows="5" maxlength="500" required aria-describedby="modal-hint"
                          class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm"></textarea>
                <p id="modal-hint" class="mt-1 text-xs text-slate-500">
                    @if ($message !== '' && $message === ($ws->ai_findings['message'] ?? null))
                        Filled in from the AI suggestion. Read it and edit before sending.
                    @else
                        Up to 500 characters.
                    @endif
                </p>

                @if ($modal === 'void')
                    <label class="mt-3 flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="reissue" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                        <span>Also create a corrected draft for the student (their answers are copied; they fix and resubmit; it needs review and signatures again).</span>
                    </label>
                @endif

                @error('action') <p class="mt-2 text-sm font-medium text-rose-700" role="alert">{{ $message }}</p> @enderror

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" wire:click="closeModal" class="{{ $secondary }}">Cancel</button>
                    <button type="button" wire:click="submitModal" wire:loading.attr="disabled" wire:target="submitModal"
                            class="{{ in_array($modal, ['reject', 'void']) ? $btn . ' bg-rose-600 text-white shadow-sm hover:bg-rose-700' : $primary }}">
                        {{ ['request' => 'Send request', 'reject' => 'Reject transaction', 'void' => 'Void document'][$modal] }}
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
