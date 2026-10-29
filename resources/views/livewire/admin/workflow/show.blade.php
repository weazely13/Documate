@php
    $s = $ws->workflow_status->value;
    $hasApprovals = $approvals->isNotEmpty();
    $anyApproved = $approvals->contains(fn ($a) => $a->status->value === 'approved');
    $noHolder = $chainPreview->contains(fn ($c) => empty($c['holders']));

    $btn = 'w-full rounded-lg px-4 py-2.5 text-sm font-semibold transition disabled:opacity-50 sm:w-auto';
    $primary = $btn . ' bg-[#2A57B4] text-white hover:bg-[#24499A]';
    $secondary = $btn . ' border border-slate-300 bg-white text-slate-700 hover:bg-slate-50';
    $danger = $btn . ' border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100';

    $card = 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5';
    $actions = 'mt-4 flex flex-col gap-2 sm:flex-row sm:flex-wrap';
    $input = 'mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-base sm:py-2 sm:text-sm';

    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
@endphp

<div class="mx-auto max-w-6xl space-y-5">

    @php
        $who = $ws->field_values['student_name'] ?? ($ws->user?->full_name ?? '');
        $headerLine = collect([
            $who ?: null,
            $ws->user?->student_number,
            $document->name(),
            $issuer ? 'Issued by ' . $issuer : null,
        ])->filter()->implode(' · ');
    @endphp
    <x-admin.page-header
        :title="$ws->transaction_code"
        :description="$headerLine"
        :crumbs="['Admin' => $homeUrl, $this->backLabel => $this->backUrl, $ws->transaction_code => null]">
        <x-slot:actions>
            <a href="{{ $this->backUrl }}" wire:navigate
               class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 sm:w-auto sm:py-2">
                <i class='bx bx-arrow-back'></i> Back to {{ strtolower($this->backLabel) }}
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if ($notice)
        <x-admin.alert :message="$notice" type="success" class="!mb-0" wire:key="notice-{{ md5($notice) }}" />
    @endif
    @if (! $modal && $errors->has('action'))
        <x-admin.alert :message="$errors->first('action')" type="danger" class="!mb-0" wire:key="error-{{ md5($errors->first('action')) }}" />
    @endif

    {{-- What to do next --}}
    <section class="{{ $card }}" aria-labelledby="next-step">
        @switch($s)
            @case('submitted')
                <p id="next-step" class="text-sm font-semibold text-slate-900">New submission</p>
                <p class="mt-0.5 text-sm text-slate-500">Read the answers below, then start the review.</p>
                <div class="{{ $actions }}">
                    <button type="button" wire:click="startReview" wire:loading.attr="disabled" class="{{ $primary }}">Start review</button>
                    <button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject</button>
                </div>
                @break

            @case('under_review')
                @if (! $hasApprovals)
                    <p id="next-step" class="text-sm font-semibold text-slate-900">Under review</p>
                    <p class="mt-0.5 text-sm text-slate-500">If the answers are in order, send it for signing. The approval chain below will open.</p>

                    <ol class="mt-4 space-y-2">
                        @foreach ($chainPreview as $i => $c)
                            <li class="flex items-start gap-3 rounded-lg bg-slate-50 px-3.5 py-2.5" wire:key="cp-{{ $i }}">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-white text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">{{ $i + 1 }}</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-slate-800">{{ $c['label'] }}: {{ $c['role'] }}</p>
                                    @if ($c['holders'])
                                        <p class="break-words text-xs text-slate-500">{{ implode(', ', $c['holders']) }}</p>
                                    @else
                                        <p class="text-xs font-medium text-amber-600">No one holds this role right now. Assign someone under Manage Users &gt; Signatories first.</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>

                    @if ($reviewerFields)
                        <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p class="text-sm font-semibold text-slate-900">Fill in before sending</p>
                            <p class="mt-0.5 text-xs text-slate-500">These are completed by the VPSD office and printed on the document.</p>
                            <div class="mt-3 space-y-3">
                                @foreach ($reviewerFields as $name => $f)
                                    <div wire:key="rf-{{ $name }}">
                                        <label class="block text-xs font-medium text-slate-600">
                                            {{ $f['label'] }} @if ($f['required'] ?? false)<span class="text-rose-500">*</span>@endif
                                        </label>
                                        <input type="{{ ['date' => 'date', 'time' => 'time'][$f['type']] ?? 'text' }}"
                                            wire:model="reviewerValues.{{ $name }}"
                                            @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                            class="{{ $input }}">
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($needsIdentityCheck)
                        <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                            <p class="text-sm font-semibold text-amber-900">Check the rater before sending</p>
                            <p class="mt-0.5 text-xs text-amber-800">
                                Confirm this person is real and agreed to rate the student. Use contact details from official records, not the ones the student typed.
                            </p>

                            <label class="mt-3 block text-xs font-medium text-amber-900">How did you verify them?</label>
                            <select wire:model.live="identityMethod" class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2.5 text-base sm:py-2 sm:text-sm">
                                <option value="">Choose…</option>
                                @foreach ($identityMethods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('identityMethod') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

                            <label class="mt-3 block text-xs font-medium text-amber-900">Note (optional)</label>
                            <input type="text" wire:model="identityNote" maxlength="300" placeholder="e.g. Confirmed with HR, ext. 214"
                                class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2.5 text-base sm:py-2 sm:text-sm">
                            <p class="mt-1 text-[11px] text-amber-700">Don't write ID numbers or personal phone numbers here.</p>

                            <label class="mt-3 flex items-start gap-2 text-sm text-amber-900">
                                <input type="checkbox" wire:model.live="identityConfirmed" class="mt-0.5 h-4 w-4 accent-[#2A57B4]">
                                <span>I confirm this person's identity and that they agreed to rate this student.</span>
                            </label>
                            @error('identityConfirmed') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                        </div>
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
                    <p id="next-step" class="text-sm font-semibold text-slate-900">Out for signing</p>
                    <p class="mt-0.5 text-sm text-slate-500">
                        @if ($open) Waiting for {{ $open->role->label ?? 'the signatory' }}. @endif
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
                <p id="next-step" class="text-sm font-semibold text-slate-900">Waiting on the student</p>
                <p class="mt-0.5 text-sm text-slate-500">You asked: {{ $ws->workflow_note }}</p>
                <div class="{{ $actions }}">
                    <button type="button" wire:click="openModal('reject')" class="{{ $danger }}">Reject instead</button>
                </div>
                @break

            @case('approved')
                <p id="next-step" class="text-sm font-semibold text-slate-900">Final verification</p>
                <p class="mt-0.5 text-sm text-slate-500">Confirm each point, then complete. This seals the record and issues the PDF. It cannot be undone.</p>
                <ul class="mt-3 space-y-1.5 text-sm text-slate-700">
                    <li class="flex gap-2"><i class='bx bx-check-circle text-lg text-emerald-600'></i> All {{ $approvals->count() }} signatures collected</li>
                    <li class="flex gap-2"><i class='bx bx-check-circle text-lg text-emerald-600'></i> Answers were reviewed and accepted</li>
                    <li class="flex gap-2"><i class='bx bx-check-circle text-lg text-emerald-600'></i> Nothing is waiting on the student</li>
                </ul>
                <div class="{{ $actions }}">
                    <button type="button" wire:click="complete" wire:loading.attr="disabled"
                            wire:confirm="Complete this transaction? The record will be sealed and can no longer change."
                            class="{{ $primary }}">Complete and issue</button>
                </div>
                @break

            @case('completed')
                <p id="next-step" class="text-sm font-semibold text-emerald-700">Completed {{ $ws->completed_at?->format('M j, Y g:i A') }}</p>
                <div class="{{ $actions }}">
                    <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled" class="{{ $primary }}">Download official PDF</button>
                    <button type="button" wire:click="openModal('void')" class="{{ $danger }}">Void this document</button>
                </div>
                @break

            @case('rejected')
                <p id="next-step" class="text-sm font-semibold text-rose-700">Rejected</p>
                <p class="mt-0.5 text-sm text-slate-600">{{ $ws->workflow_note }}</p>
                @break

            @case('voided')
                <p id="next-step" class="text-sm font-semibold text-slate-700">Voided {{ $ws->voided_at?->format('M j, Y g:i A') }}</p>
                <p class="mt-0.5 text-sm text-slate-600">{{ $ws->void_reason }}</p>
                @if ($replacementCode)
                    <p class="mt-2 text-xs text-slate-500">Corrected draft: <span class="font-mono font-semibold">{{ $replacementCode }}</span>. It appears here once the student resubmits it.</p>
                @endif
                @break
        @endswitch
    </section>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_340px]">

        {{-- Main column --}}
        <div class="min-w-0 space-y-5">
            @if (! empty($ws->field_values['item_photo_path']))
                <section class="{{ $card }}">
                    <h3 class="text-sm font-semibold text-slate-900">Item photo</h3>
                    <p class="text-xs text-slate-400">For identification only. Not part of the PDF.</p>
                    <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}" alt="Item photo"
                        class="mt-3 max-h-72 max-w-full rounded-lg border border-slate-200 bg-white object-contain p-1">
                </section>
            @endif

            <section class="{{ $card }}">
                <h3 class="text-sm font-semibold text-slate-900">Document preview</h3>
                <p class="text-xs text-slate-400">A preview of the document layout. The downloaded PDF is the official copy, so spacing may differ slightly.</p>
                <div class="mt-3">
                    @include('livewire.workflow._preview', [
                        'src' => route('admin.workflow.preview', $ws->workspace_id),
                        'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
                    ])
                </div>
            </section>

            @if ($hasApprovals)
                <section class="{{ $card }}">
                    <h3 class="text-sm font-semibold text-slate-900">Signatures</h3>
                    <div class="mt-3">@include('livewire.workflow._chain', ['approvals' => $approvals])</div>
                </section>
            @endif

            @if ($seal)
                <section class="rounded-xl border p-4 shadow-sm sm:p-5 {{ $seal['valid'] ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                    <h3 class="text-sm font-semibold {{ $seal['valid'] ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $seal['valid'] ? 'Seal intact' : 'Seal check failed' }}
                    </h3>
                    <p class="mt-0.5 break-all text-xs {{ $seal['valid'] ? 'text-emerald-700' : 'text-rose-700' }}">Fingerprint {{ $seal['fingerprint'] }}. Rechecked from the database every time you open this page.</p>
                    <ul class="mt-3 space-y-1 text-sm text-slate-700">
                        @foreach (['content_ok' => 'Answers match the sealed fingerprint', 'signers_ok' => 'Every signature seal and image is intact', 'audit_ok' => 'Activity history was not altered'] as $k => $label)
                            <li class="flex items-center gap-2"><i class='bx {{ $seal[$k] ? "bx-check-circle text-emerald-600" : "bx-x-circle text-rose-600" }} text-lg'></i> {{ $label }}</li>
                        @endforeach
                    </ul>
                    <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" rel="noopener"
                       class="mt-3 inline-block text-xs font-semibold text-[#2A57B4] hover:underline">Open the public verification page</a>
                </section>
            @endif
        </div>

        {{-- Activity --}}
        <aside class="{{ $card }} h-fit lg:sticky lg:top-6 lg:max-h-[calc(100vh-3rem)] lg:overflow-y-auto" aria-label="Activity">
            <h3 class="text-sm font-semibold text-slate-900">Activity</h3>
            <p class="text-xs text-slate-400">Newest first</p>
            <div class="mt-4 space-y-4">
                @foreach ($audit as $i => $item)
                    <div class="relative pl-5" wire:key="audit-{{ $i }}">
                        <span class="absolute left-0 top-1.5 h-2 w-2 rounded-full bg-slate-300"></span>
                        <p class="text-sm font-medium text-slate-800">{{ $item['text'] }}</p>
                        <p class="text-xs text-slate-500">{{ $item['who'] }}{{ $item['role'] ? ' (' . $item['role'] . ')' : '' }}</p>
                        <p class="text-xs text-slate-400">{{ $item['at']->format('M j, Y g:i A') }}</p>
                        @if ($item['note']) <p class="mt-0.5 break-words text-xs text-slate-500">{{ $item['note'] }}</p> @endif
                    </div>
                @endforeach
            </div>
        </aside>
    </div>

    {{-- Request changes / Reject / Void --}}
    @if ($modal)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 backdrop-blur-sm sm:items-center sm:p-4"
             wire:keydown.escape="closeModal" wire:click.self="closeModal" role="dialog" aria-modal="true">
            <div class="max-h-[92dvh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
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
                    class="mt-4 w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-base outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100 sm:text-sm"></textarea>

                @if ($modal === 'void')
                    <label class="mt-3 flex items-start gap-2 text-sm text-slate-700">
                        <input type="checkbox" wire:model="reissue" class="mt-0.5 h-4 w-4 accent-[#2A57B4]">
                        <span>Also create a corrected draft for the student (their answers are copied; they fix and resubmit; it needs review and signatures again).</span>
                    </label>
                @endif

                @error('action') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
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