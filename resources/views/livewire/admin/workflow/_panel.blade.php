{{-- Workflow Review side panel: one transaction at a glance, its AI check, and the decisions that can be made here.
     Beside the list on very wide screens (2xl), a full-height drawer otherwise (full width on phones). --}}
@php
    $ws = $panel['ws'];
    $doc = $panel['document'];
    $status = $statusFor($ws, $panel['queue']);
    $hasDecisions = $panel['canSend'] || $panel['canRequest'] || $panel['canReject'] || $panel['canFinish'];
    $previewSrc = route('admin.workflow.preview', $ws->workspace_id);
    $previewVersion = $ws->updated_at?->timestamp . '-' . $panel['signed'];
    $iconBtn = 'grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 ' . $focus;
@endphp

{{-- Backdrop for the drawer --}}
<div class="fixed inset-0 z-40 bg-slate-900/30 2xl:hidden" wire:click="closePanel" aria-hidden="true"></div>

<aside id="wf-panel" wire:key="panel-{{ $ws->workspace_id }}"
       role="region" aria-labelledby="wf-panel-title"
       x-data="{
            {{-- The page knows the panel is open, so the floating assistant button can step aside. --}}
            init() { document.body.classList.add('wf-panel-open'); this.$nextTick(() => this.$refs.title.focus()); },
            destroy() { document.body.classList.remove('wf-panel-open'); }
       }"
       x-on:livewire:navigating.window="document.body.classList.remove('wf-panel-open')"
       @keydown.escape.window="if (! $wire.decision && ! window.documatePreviewOpen && ! document.querySelector('.chatbot-panel-open')) $wire.closePanel()"
       class="fixed inset-y-0 right-0 z-50 flex w-full flex-col bg-white shadow-2xl sm:max-w-[440px]
              2xl:sticky 2xl:inset-auto 2xl:top-0 2xl:z-auto 2xl:max-h-[calc(100dvh-8.5rem)] 2xl:w-auto 2xl:max-w-none 2xl:rounded-2xl 2xl:border 2xl:border-slate-200 2xl:shadow-sm">

    {{-- Top bar: back (phones), code, preview, full record, close --}}
    <div class="flex items-center justify-between gap-2 border-b border-slate-100 py-2 pl-2 pr-2 sm:pl-5 sm:pr-3">
        <div class="flex min-w-0 items-center gap-1">
            <button type="button" wire:click="closePanel" class="{{ $iconBtn }} sm:hidden" aria-label="Back to the list">
                <i class="bx bx-arrow-back text-xl" aria-hidden="true"></i>
            </button>
            <p class="truncate text-sm font-medium tabular-nums text-slate-700">{{ $ws->transaction_code }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-1">
            <button type="button" @click="$dispatch('open-doc-preview')" class="{{ $iconBtn }}"
                    aria-label="Preview the document" title="Preview the document">
                <i class="bx bx-show text-xl" aria-hidden="true"></i>
            </button>
            <a href="{{ route('admin.workflow.show', $ws->workspace_id) }}" wire:navigate class="{{ $iconBtn }}"
               aria-label="Open the full record" title="Open the full record">
                <i class="bx bx-expand-alt text-lg" aria-hidden="true"></i>
            </a>
            <button type="button" wire:click="closePanel" class="{{ $iconBtn }} max-sm:hidden" aria-label="Close details">
                <i class="bx bx-x text-2xl" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="flex-1 space-y-6 overflow-y-auto overscroll-contain px-4 py-5 sm:px-5">

        {{-- Title --}}
        <div class="flex items-start gap-3">
            <span class="relative grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full bg-[#e2e9f7] text-[13px] font-semibold text-[#1E4090]" aria-hidden="true">
                {{ $initialsFor($ws) }}
                @if ($avatarFor($ws))
                    <img src="{{ $avatarFor($ws) }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover" onerror="this.remove()">
                @endif
            </span>
            <div class="min-w-0">
                <h2 id="wf-panel-title" x-ref="title" tabindex="-1" class="break-words rounded text-lg font-semibold leading-snug tracking-[-0.012em] text-slate-900 focus:outline-none">
                    {{ $nameFor($ws) }}
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $doc?->name() }}@if ($ws->updated_at) · last updated {{ $ws->updated_at->format('M j, g:i A') }}@endif
                </p>
            </div>
        </div>

        @include('livewire.admin.workflow._record-props', ['ws' => $ws, 'doc' => $doc, 'status' => $status, 'issuer' => $panel['issuer'], 'waiting' => $panel['waiting']])

        {{-- Decide --}}
        @if ($hasDecisions)
            <section aria-labelledby="wf-decide">
                <h3 id="wf-decide" class="text-sm font-semibold text-slate-900">Decide</h3>

                @if (! $decision && $errors->has('action'))
                    <p class="mt-2 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">{{ $errors->first('action') }}</p>
                @endif

                @if ($panel['canFinish'])
                    <p class="mt-1 text-sm text-slate-500">Everyone has signed. Completing seals the record and issues the PDF. It cannot be undone.</p>
                @elseif ($panel['canSend'] && $panel['sendBlocker'])
                    <p class="mt-1 text-sm text-slate-500">{{ $panel['sendBlocker'] }}</p>
                @elseif ($panel['canSend'])
                    <p class="mt-1 text-sm text-slate-500">If the answers are in order, send it for signing. The first signatory is notified.</p>
                @endif

                <div class="mt-3 grid gap-2 sm:flex sm:flex-wrap">
                    @if ($panel['canFinish'])
                        <button type="button" wire:click="finish({{ $ws->workspace_id }})" wire:loading.attr="disabled" wire:target="finish"
                                wire:confirm="Complete this transaction? The record will be sealed and can no longer change."
                                class="{{ $btnPrimary }}">Complete and issue</button>
                    @endif
                    @if ($panel['canSend'] && ! $panel['sendBlocker'])
                        <button type="button" wire:click="quickSend({{ $ws->workspace_id }})" wire:loading.attr="disabled" wire:target="quickSend"
                                wire:confirm="Send this transaction for signing?"
                                class="{{ $btnPrimary }}">Send for signing</button>
                    @elseif ($panel['canSend'])
                        <a href="{{ route('admin.workflow.show', $ws->workspace_id) }}" wire:navigate class="{{ $btnPrimary }}">Open to send for signing</a>
                    @endif
                    @if ($panel['canRequest'])
                        <button type="button" wire:click="openDecision('request')" class="{{ $btnSecondary }}">Request changes</button>
                    @endif
                    @if ($panel['canReject'])
                        <button type="button" wire:click="openDecision('reject')" class="{{ $btnDanger }}">Reject</button>
                    @endif
                </div>
            </section>
        @endif

        @if ($ws->workflow_status->value === 'action_required' && $ws->workflow_note)
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-3.5 py-3">
                <p class="flex items-center gap-1.5 text-sm font-medium text-amber-900">
                    <i class="bx bx-hourglass text-base" aria-hidden="true"></i> Waiting on the student
                </p>
                <p class="mt-0.5 break-words text-sm text-amber-900">You asked: {{ $ws->workflow_note }}</p>
            </div>
        @endif

        {{-- AI check --}}
        @if ($panel['checkable'])
            <div class="rounded-xl border border-slate-200 p-4">
                @include('livewire.admin.workflow._ai-check', ['ws' => $ws, 'run' => 'runAiCheck(' . $ws->workspace_id . ')', 'poll' => false])
            </div>
        @endif

        @include('livewire.admin.workflow._record-timeline', [
            'approvals' => $panel['approvals'], 'audit' => $panel['audit'], 'idPrefix' => 'wf',
            'reportUrl' => route('admin.workflow.report', $ws->workspace_id), 'reportEvent' => 'open-report-preview',
        ])

        <a href="{{ route('admin.workflow.show', $ws->workspace_id) }}" wire:navigate
           class="inline-flex items-center gap-1 rounded text-sm font-medium text-[#2A57B4] hover:underline {{ $focus }}">
            Full record and document preview <i class="bx bx-right-arrow-alt text-base" aria-hidden="true"></i>
        </a>
    </div>

    @include('livewire.admin.workflow._preview-modal', ['src' => $previewSrc, 'version' => $previewVersion, 'title' => ($doc?->name() ?? 'Document') . ' · ' . $ws->transaction_code])
    @include('livewire.admin.workflow._preview-modal', [
        'event' => 'open-report-preview',
        'src' => route('admin.workflow.report.preview', $ws->workspace_id),
        'version' => $previewVersion . '-' . $panel['audit']->count(),
        'title' => 'Audit trail · ' . $ws->transaction_code,
        'note' => 'Everything that happened to this transaction. Download it as a PDF to keep a copy.',
        'download' => route('admin.workflow.report', $ws->workspace_id),
    ])
</aside>
