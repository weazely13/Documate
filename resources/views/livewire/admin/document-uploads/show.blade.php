@php
    $docStatus = $workspace->documentVerificationStatus();
    [$statusText, $statusTag] = match ($docStatus) {
        'Verified' => ['Verified', 'bg-emerald-600 text-white'],
        'Reviewing' => ['Being checked', 'bg-[#2A57B4] text-white'],
        'Needs Re-upload' => ['Sent back', 'bg-rose-600 text-white'],
        'Needs Upload' => ['Needs upload', 'bg-amber-500 text-white'],
        default => ['Awaiting visit', 'bg-slate-500 text-white'],
    };
    // Where the paper form is: 0 visit, 1 upload, 2 AI check, 3 verified
    $stage = match ($docStatus) {
        'Verified' => 4,
        'Reviewing' => 2,
        'Needs Upload', 'Needs Re-upload' => 1,
        default => 0,
    };
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]/40 focus-visible:ring-offset-1';

    $card = 'rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5';
    $studentName = ($workspace->user?->full_name ?? '');
    $templateName = $workspace->template?->name ?? 'Document Workspace';
@endphp

<div x-data="{ confirmReupload: false }" class="mx-auto max-w-7xl pb-16" wire:poll.2s="pollStatus">
    {{-- Title bar: back, the form and where it stands --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ route('admin.document-uploads.index') }}" wire:navigate aria-label="Back to uploads" title="Back to uploads"
           class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
            <i class="bx bx-arrow-back text-lg" aria-hidden="true"></i>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xl font-semibold tracking-[-0.015em] text-slate-900 sm:text-2xl">
                <span class="min-w-0">{{ $templateName }}</span>
                <span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $statusTag }}">{{ $statusText }}</span>
            </h1>
            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $studentName ?: 'Unknown student' }} · {{ $workspace->user?->student_number ?: 'No student number' }} · paper form</p>
        </div>
        <a href="{{ route('admin.transactions.show', ['workspace' => $workspace->workspace_id, 'back' => 'upload']) }}" wire:navigate
           class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 [@media(pointer:coarse)]:h-11 {{ $focus }}">
            <i class="bx bx-file text-base" aria-hidden="true"></i> Full record
        </a>
    </div>

    {{-- Engine Partials --}}
    @include('livewire.student.partials.preview-engine')

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        {{-- Left: the check, step by step --}}
        <div class="min-w-0 space-y-5">
            <ol class="grid grid-cols-4 gap-2" aria-label="Progress">
                @foreach (['Visited the office', 'Signed copy uploaded', 'AI check', 'Verified'] as $i => $stepLabel)
                    @php
                        $done = $i < $stage;
                        $now = $i === $stage && $stage < 4;
                        $bar = $done ? 'bg-emerald-500' : ($now ? ($docStatus === 'Needs Re-upload' ? 'bg-rose-500' : 'bg-[#2A57B4]') : 'bg-slate-200');
                    @endphp
                    <li class="min-w-0" @if ($now) aria-current="step" @endif>
                        <div class="h-1.5 rounded-full {{ $bar }}" aria-hidden="true"></div>
                        <p class="mt-1.5 text-[11px] leading-tight sm:text-xs {{ $now ? 'font-semibold text-slate-900' : ($done ? 'text-slate-700' : 'text-slate-500') }}">{{ $stepLabel }}</p>
                    </li>
                @endforeach
            </ol>

            {{-- Upload / verification status --}}
            <div class="{{ $card }}">

                @if(!empty($workspace->analysis_data['error_code']))
                    <div class="mb-4 break-words rounded-lg border border-rose-200 bg-rose-50/50 p-3 text-xs text-rose-600">
                        <span class="font-semibold">Technical detail:</span> {{ $workspace->analysis_data['error_code'] }}
                        — {{ $workspace->analysis_data['error_message'] ?? '' }}
                    </div>
                @endif

                @if($workspace->analysis_status === 'approved')
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <span class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700">
                            <i class='bx bx-check-circle text-lg'></i>
                            Document verified. The transaction is complete.
                        </span>
                        <button type="button" @click="confirmReupload = true"
                            class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 sm:w-auto sm:py-2">
                            <i class='bx bx-refresh'></i> Replace photo
                        </button>
                    </div>
                @elseif($workspace->isProcessing())
                    <div class="flex items-center gap-3.5 rounded-lg border border-amber-200/80 bg-amber-50/60 p-4 text-sm text-amber-900" wire:poll.3s>
                        <span class="relative flex h-3 w-3 shrink-0">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full bg-amber-500"></span>
                        </span>
                        <div class="space-y-0.5">
                            <p class="font-semibold">{{ $workspace->processing_stage ?: 'Reviewing the document photo...' }}</p>
                            <p class="text-xs text-amber-700">This page updates automatically.</p>
                        </div>
                    </div>
                @elseif(!$this->canUpload)
                    <div class="rounded-lg border border-slate-200 bg-slate-50/60 p-4 text-sm text-slate-500">
                        <h4 class="font-semibold text-slate-700">Verification photo upload</h4>
                        <p class="mt-1">This unlocks once the student's appointment is marked as <strong class="text-slate-700">attended</strong>.</p>
                    </div>
                @else
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">Upload final document photo</h4>
                        <p class="mt-0.5 text-sm text-slate-500">Upload a clear photo of the document processed for this student to complete the transaction.</p>

                        @if($workspace->analysis_status === 'rejected' && $workspace->rejection_reason)
                            @php $isTechnicalFailure = !empty($workspace->analysis_data['error_code'] ?? null); @endphp
                            <div class="mt-3 flex items-start gap-2.5 rounded-lg border p-3 text-sm {{ $isTechnicalFailure ? 'border-blue-200 bg-blue-50/70 text-blue-800' : 'border-rose-200 bg-rose-50/70 text-rose-800' }}">
                                <i class='bx {{ $isTechnicalFailure ? "bx-info-circle text-blue-500" : "bx-error-circle text-rose-500" }} text-lg shrink-0 mt-0.5'></i>
                                <div>
                                    <p class="font-semibold">{{ $isTechnicalFailure ? "Verification couldn't finish. Please retry." : "Document photo not accepted" }}</p>
                                    <p class="mt-0.5 leading-relaxed">{{ $workspace->rejection_reason }}</p>
                                </div>
                            </div>
                        @endif

                        <form
                            x-data="{
                                capturing: false,
                                async submitUpload() {
                                    this.capturing = true;
                                    const el = document.getElementById('document-preview-canvas-mobile')
                                        ?? document.getElementById('document-preview-canvas');
                                    const visibleEl = (el && el.offsetParent !== null) ? el
                                        : document.getElementById('document-preview-canvas');
                                    const wrap = document.getElementById('doc-scale-wrap');
                                    const shrunk = wrap ? wrap.style.transform : '';
                                    if (wrap) wrap.style.transform = 'none';
                                    try {
                                        if (el && window.html2canvas) {
                                            const canvas = await html2canvas(visibleEl, { backgroundColor: '#ffffff', scale: 1, useCORS: true });
                                            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                                            await $wire.set('referenceImageBase64', dataUrl);
                                        }
                                    } catch (e) {
                                        console.warn('Could not capture preview frame', e);
                                    } finally {
                                        if (wrap) wrap.style.transform = shrunk;
                                        this.capturing = false;
                                        $wire.uploadSupportingFile();
                                    }
                                }
                            }"
                            @submit.prevent="submitUpload"
                            class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center"
                        >
                            <input type="file" wire:model="supportingFile" accept=".jpg,.jpeg,.png,.pdf"
                                class="block w-full min-w-0 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-200/60 file:px-3 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">

                            <button type="submit" :disabled="capturing" wire:loading.attr="disabled" wire:target="uploadSupportingFile"
                                class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-[#24499A] disabled:opacity-50 sm:w-auto">
                                <span x-show="!capturing" wire:loading.remove wire:target="uploadSupportingFile">Upload file</span>
                                <span x-show="capturing" class="flex items-center gap-1.5">
                                    <i class='bx bx-loader-alt animate-spin'></i> Preparing...
                                </span>
                                <span wire:loading wire:target="uploadSupportingFile" class="flex items-center gap-1.5">
                                    <i class='bx bx-loader-alt animate-spin'></i> Uploading...
                                </span>
                            </button>
                        </form>
                        @error('supportingFile') <p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            {{-- Uploaded photo --}}
            @if($workspace->supporting_file_path)
                <div class="{{ $card }}">
                    <h3 class="text-sm font-semibold text-slate-900">
                        {{ $workspace->analysis_status === 'approved' ? 'Approved document attachment' : 'Uploaded verification photo' }}
                    </h3>

                    @if($this->supportingFileUrl)
                        <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="h-32 w-full shrink-0 overflow-hidden rounded-lg border border-slate-200/80 bg-slate-50 sm:h-28 sm:w-28">
                                @if($this->supportingFileIsImage)
                                    <img src="{{ $this->supportingFileUrl }}" class="h-full w-full object-cover" alt="Uploaded document">
                                @else
                                    <div class="flex h-full items-center justify-center text-slate-400">
                                        <i class='bx bx-file-blank text-3xl'></i>
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-1.5">
                                <a href="{{ $this->supportingFileUrl }}" target="_blank" rel="noopener"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-[#2A57B4] hover:underline">
                                    <span>View full size</span>
                                    <i class='bx bx-external-link text-xs'></i>
                                </a>
                                @if($workspace->isProcessing())
                                    <p class="text-sm text-amber-600">AI review is analysing the photo.</p>
                                @elseif($workspace->analysis_status === 'approved')
                                    <p class="text-sm text-emerald-600">Verified and accepted. The record was updated.</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-sm text-slate-500">The uploaded photo did not meet quality standards. Please submit a clearer image.</p>
                    @endif
                </div>
            @endif

            {{-- AI review --}}
            @php $hasContentAnalysis = array_key_exists('matches_document', $workspace->analysis_data ?? []); @endphp
            @if($workspace->analysis_summary || $workspace->ocr_text || $hasContentAnalysis)
                <div class="{{ $card }} space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-[#2A57B4]">
                                <i class='bx bx-bot text-lg'></i>
                            </div>
                            <h3 class="text-sm font-semibold text-slate-900">AI verification summary</h3>
                        </div>

                        @if($hasContentAnalysis)
                            @php
                                $isApproved = ($workspace->analysis_data['matches_document'] ?? false)
                                    && ($workspace->analysis_data['readable'] ?? false)
                                    && ($workspace->analysis_data['fields_complete'] ?? false)
                                    && ($workspace->analysis_data['has_signature_or_stamp'] ?? false)
                                    && ($workspace->analysis_data['fields_match_expected'] ?? true);
                            @endphp
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $isApproved ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20' }}">
                                <i class='bx {{ $isApproved ? "bx-check-circle" : "bx-error-circle" }}'></i>
                                {{ $isApproved ? 'Passed' : 'Attention required' }}
                            </span>
                        @endif
                    </div>

                    @if($workspace->analysis_summary)
                        <blockquote class="rounded-lg border-l-4 border-slate-300 bg-slate-50/80 p-3 text-sm italic text-slate-600">
                            "{{ $workspace->analysis_summary }}"
                        </blockquote>
                    @endif

                    @if(!empty($workspace->analysis_data))
                        @php
                            $checks = [
                                'matches_document' => 'Document layout match',
                                'readable' => 'Text legibility and clarity',
                                'fields_complete' => 'All mandatory fields populated',
                                'has_signature_or_stamp' => 'Official stamp or signature',
                                'fields_match_expected' => 'Field data matches record',
                            ];
                        @endphp
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($checks as $key => $label)
                                @php $pass = (bool) ($workspace->analysis_data[$key] ?? ($key === 'fields_match_expected')); @endphp
                                <div class="flex items-center gap-2 rounded-lg border border-slate-100 p-2.5 text-sm">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $pass ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                                        <i class='bx {{ $pass ? "bx-check" : "bx-x" }}'></i>
                                    </span>
                                    <span class="{{ $pass ? 'font-medium text-slate-700' : 'text-slate-500' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if(!empty($workspace->analysis_data['missing_fields']))
                            <div class="rounded-lg border border-amber-200/60 bg-amber-50/50 p-3">
                                <p class="text-sm font-semibold text-amber-800">Incomplete fields detected</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($workspace->analysis_data['missing_fields'] as $field)
                                        <span class="rounded-md border border-amber-300/60 bg-white px-2 py-0.5 text-xs font-medium text-amber-800">{{ $field }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($workspace->analysis_data['field_matches']))
                            <div class="space-y-2">
                                <p class="text-sm font-semibold text-slate-700">Detailed data validation</p>

                                {{-- Phones: one block per field --}}
                                <ul class="space-y-2 md:hidden">
                                    @foreach($workspace->analysis_data['field_matches'] as $fm)
                                        <li class="rounded-lg border border-slate-200/80 p-3 text-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <p class="font-medium text-slate-800">{{ $fm['label'] ?? '—' }}</p>
                                                @if($fm['matches'] ?? false)
                                                    <i class='bx bx-check-circle text-lg text-emerald-600'></i>
                                                @else
                                                    <i class='bx bx-x-circle text-lg text-rose-600'></i>
                                                @endif
                                            </div>
                                            <dl class="mt-1.5 space-y-0.5 text-xs">
                                                <div class="flex justify-between gap-3"><dt class="text-slate-400">System record</dt><dd class="break-words text-right text-slate-600">{{ $fm['expected_value'] ?? '—' }}</dd></div>
                                                <div class="flex justify-between gap-3"><dt class="text-slate-400">Document value</dt><dd class="break-words text-right text-slate-600">{{ $fm['found_value'] ?? '—' }}</dd></div>
                                            </dl>
                                        </li>
                                    @endforeach
                                </ul>

                                {{-- md and up: table --}}
                                <div class="hidden overflow-x-auto rounded-lg border border-slate-200/80 md:block">
                                    <table class="w-full text-left text-xs">
                                        <thead class="border-b border-slate-200 bg-slate-50 font-medium text-slate-500">
                                            <tr>
                                                <th class="px-3 py-2">Field</th>
                                                <th class="px-3 py-2">System record</th>
                                                <th class="px-3 py-2">Document value</th>
                                                <th class="px-3 py-2 text-center">Status</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($workspace->analysis_data['field_matches'] as $fm)
                                                <tr class="hover:bg-slate-50/50">
                                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $fm['label'] ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $fm['expected_value'] ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $fm['found_value'] ?? '—' }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        @if($fm['matches'] ?? false)
                                                            <i class='bx bx-check-circle text-base text-emerald-600'></i>
                                                        @else
                                                            <i class='bx bx-x-circle text-base text-rose-600'></i>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if($workspace->ocr_text)
                        <details class="group rounded-lg border border-slate-200/80 bg-slate-50/50 p-3">
                            <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-600 group-open:text-slate-900">
                                <span>View extracted text</span>
                                <i class='bx bx-chevron-down text-base transition-transform group-open:rotate-180'></i>
                            </summary>
                            <p class="mt-2 whitespace-pre-wrap break-words border-t border-slate-200/60 pt-2 font-mono text-[11px] leading-relaxed text-slate-600">{{ $workspace->ocr_text }}</p>
                        </details>
                    @endif
                </div>
            @endif
            <div class="{{ $card }}">
                <h3 class="text-sm font-semibold text-slate-900">Details</h3>
                <dl class="mt-3 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Student</dt>
                        <dd class="mt-0.5 flex items-center gap-2 font-semibold text-slate-700">
                            <x-dm.avatar :user="$workspace->user" :name="$studentName" class="h-6 w-6 text-[9px]" />
                            {{ $studentName ?: 'Unknown student' }} <span class="font-normal text-slate-500">· {{ $workspace->user?->student_number ?: 'No student number' }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-400">Created</dt>
                        <dd class="mt-0.5 font-semibold text-slate-700">{{ $workspace->created_at->format('F j, Y g:i A') }}</dd>
                    </div>
                    @if($appointment = $workspace->appointments->firstWhere('status', 'attended'))
                        <div>
                            <dt class="text-xs font-medium text-slate-400">Appointment attended</dt>
                            <dd class="mt-0.5 font-semibold text-slate-700">{{ $appointment->updated_at->format('F j, Y g:i A') }}</dd>
                        </div>
                    @endif
                    @if($workspace->completed_at)
                        <div>
                            <dt class="text-xs font-medium text-slate-400">Transaction completed</dt>
                            <dd class="mt-0.5 font-semibold text-emerald-700">{{ $workspace->completed_at->format('F j, Y g:i A') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>

        {{-- Right: the form as the student filled it in --}}
        <div class="min-w-0 lg:sticky lg:top-4 lg:self-start">
            <div class="mb-2 flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">The form as the student filled it in</h2>
                    <p class="text-xs text-slate-500">The uploaded photo is checked against this page.</p>
                </div>
            </div>
            {{-- Document preview --}}
            <div class="rounded-xl bg-slate-200/70 p-2 sm:p-3"
                x-data="documentPreviewEngine(@js($this->fields), @js($workspace->field_values ?? []))">

                {{-- Mobile: fit to screen with pinch zoom --}}
                <div
                    x-data="pinchZoomPreview({ contentWidth: {{ $this->canvas['width'] ?? 800 }}, contentHeight: {{ $this->canvas['height'] ?? 1130 }} })"
                    x-ref="zoomFrame"
                    @pointerdown="onPointerDown($event)"
                    @pointermove="onPointerMove($event)"
                    @pointerup="onPointerUp($event)"
                    @pointercancel="onPointerUp($event)"
                    class="sm:hidden relative touch-none overflow-hidden rounded-lg border border-slate-200 bg-white"
                    style="height: 65vh; height: 65dvh;">
                    <div id="document-preview-canvas-mobile"
                        class="absolute left-0 top-0 origin-top-left"
                        :style="`transform: translate(${translateX}px, ${translateY}px) scale(${scale}); width: {{ $this->canvas['width'] ?? 800 }}px; height: {{ $this->canvas['height'] ?? 1130 }}px;`">
                        @include('livewire.student.partials.preview-canvas', ['workspace' => $workspace, 'fields' => $this->fields])
                    </div>
                    <div class="absolute bottom-2 right-2 flex items-center gap-1 rounded-lg border border-slate-200 bg-white/95 p-1 shadow-sm">
                        <button type="button" @click="zoomBy(-0.25)" aria-label="Zoom out" class="flex h-9 w-9 items-center justify-center rounded-md text-slate-600"><i class='bx bx-minus'></i></button>
                        <span class="px-1 text-[11px] font-semibold text-slate-600" x-text="Math.round(scale*100)+'%'"></span>
                        <button type="button" @click="zoomBy(0.25)" aria-label="Zoom in" class="flex h-9 w-9 items-center justify-center rounded-md text-slate-600"><i class='bx bx-plus'></i></button>
                        <button type="button" @click="reset()" class="rounded-md px-2 py-2 text-[11px] font-medium text-slate-500">Reset</button>
                    </div>
                </div>

                {{-- Desktop: the page shrunk to the column (un-shrunk for a moment when the upload is captured) --}}
                <div class="hidden overflow-hidden sm:block"
                     x-data="{ s: 1, w: {{ $this->canvas['width'] ?? 800 }}, h: {{ $this->canvas['height'] ?? 1130 }} }"
                     x-init="const fit = () => s = Math.min(1, $el.clientWidth / w); fit(); new ResizeObserver(fit).observe($el)"
                     :style="`height:${h * s}px`">
                    <div id="doc-scale-wrap" class="origin-top-left" :style="`transform:scale(${s});width:${w}px`">
                    <div id="document-preview-canvas"
                        class="relative mx-auto overflow-visible rounded-lg bg-white shadow-sm ring-1 ring-slate-200"
                        style="width: {{ $this->canvas['width'] ?? 800 }}px; height: {{ $this->canvas['height'] ?? 1130 }}px; max-width: none;">
                        @include('livewire.student.partials.preview-canvas', ['workspace' => $workspace, 'fields' => $this->fields])
                    </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Replace-photo confirmation --}}
    <template x-teleport="body">
        <div x-show="confirmReupload" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="confirmReupload = false"
            @click.self="confirmReupload = false"
            role="dialog" aria-modal="true"
            class="fixed inset-0 z-50 flex items-end justify-center bg-slate-900/40 backdrop-blur-sm sm:items-center sm:p-4">
            <div class="w-full max-w-sm space-y-4 rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="flex items-center gap-3 text-[#2A57B4]">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-50">
                        <i class='bx bx-refresh text-2xl'></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Replace verified photo?</h3>
                </div>
                <p class="text-sm leading-relaxed text-slate-600">
                    This document was already verified. Uploading a new photo will re-run AI review and briefly mark this transaction as processing again.
                </p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button @click="confirmReupload = false" class="w-full rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:w-auto">Cancel</button>
                    <button @click="confirmReupload = false; $wire.set('workspace.analysis_status', 'rejected')"
                        class="w-full rounded-lg bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A] sm:w-auto">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>