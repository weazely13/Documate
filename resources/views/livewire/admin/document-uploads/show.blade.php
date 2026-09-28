<div x-data="{ confirmReupload: false }" class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6" wire:poll.2s="pollStatus">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    {{ $workspace->template?->name ?? 'Document Workspace' }}
                </h2>
                @php
                    $docStatus = $workspace->documentVerificationStatus();
                    $statusBadge = match ($docStatus) {
                        'Verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                        'Reviewing' => 'bg-indigo-50 text-indigo-700 ring-indigo-600/20',
                        'Needs Re-upload' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                        default => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full {{ $statusBadge }} px-2.5 py-1 text-xs font-semibold ring-1 ring-inset">
                    {{ $docStatus }}
                </span>
            </div>
            <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 sm:text-sm">
                <i class='bx bx-user text-slate-400'></i>
                {{ $workspace->user?->first_name }} {{ $workspace->user?->last_name }}
                &middot; {{ $workspace->user?->student_number ?: 'No student number' }}
            </p>
        </div>

        <a href="{{ route('admin.document-uploads.index') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-slate-900 active:scale-[0.98]">
            <i class='bx bx-left-arrow-alt text-lg'></i>
            <span>Back to Document Uploads</span>
        </a>
    </div>

    {{-- Engine Partials --}}
    @include('livewire.student.partials.preview-engine')

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

        {{-- Primary Content Column --}}
        <div class="space-y-6">

            {{-- Document Preview Engine --}}
            <div class="rounded-2xl border border-slate-200/80 bg-slate-900/5 p-2 shadow-sm backdrop-blur-sm sm:p-4"
                x-data="documentPreviewEngine(@js($this->fields), @js($workspace->field_values ?? []))">

                {{-- Mobile: fit-to-screen + pinch zoom --}}
                <div
                    x-data="pinchZoomPreview({ contentWidth: {{ $this->canvas['width'] ?? 800 }}, contentHeight: {{ $this->canvas['height'] ?? 1130 }} })"
                    x-ref="zoomFrame"
                    @pointerdown="onPointerDown($event)"
                    @pointermove="onPointerMove($event)"
                    @pointerup="onPointerUp($event)"
                    @pointercancel="onPointerUp($event)"
                    class="sm:hidden relative touch-none overflow-hidden rounded-lg border border-slate-200 bg-white"
                    style="height: 65vh;">
                    <div id="document-preview-canvas-mobile"
                        class="absolute left-0 top-0 origin-top-left"
                        :style="`transform: translate(${translateX}px, ${translateY}px) scale(${scale}); width: {{ $this->canvas['width'] ?? 800 }}px; height: {{ $this->canvas['height'] ?? 1130 }}px;`">
                        @include('livewire.student.partials.preview-canvas', ['workspace' => $workspace, 'fields' => $this->fields])
                    </div>
                    <div class="absolute bottom-2 right-2 flex items-center gap-1 rounded-lg border border-slate-200 bg-white/95 p-1 shadow-sm">
                        <button type="button" @click="zoomBy(-0.25)" class="flex h-7 w-7 items-center justify-center rounded-md text-slate-600"><i class='bx bx-minus'></i></button>
                        <span class="px-1 text-[11px] font-semibold text-slate-600" x-text="Math.round(scale*100)+'%'"></span>
                        <button type="button" @click="zoomBy(0.25)" class="flex h-7 w-7 items-center justify-center rounded-md text-slate-600"><i class='bx bx-plus'></i></button>
                        <button type="button" @click="reset()" class="rounded-md px-1.5 text-[11px] font-medium text-slate-500">Reset</button>
                    </div>
                </div>

                {{-- Desktop: unchanged horizontal-scroll canvas --}}
                <div class="hidden -mx-2 overflow-x-auto sm:mx-0 sm:block">
                    <div id="document-preview-canvas"
                        class="relative mx-auto overflow-visible rounded-lg bg-white shadow-sm ring-1 ring-slate-200"
                        style="width: {{ $this->canvas['width'] ?? 800 }}px; height: {{ $this->canvas['height'] ?? 1130 }}px; max-width: none;">
                        @include('livewire.student.partials.preview-canvas', ['workspace' => $workspace, 'fields' => $this->fields])
                    </div>
                </div>
            </div>

            {{-- Upload / Verification Status Panel --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">

                @if(!empty($workspace->analysis_data['error_code']))
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50/50 p-3 text-xs text-rose-600">
                        <span class="font-semibold">Technical Detail:</span> {{ $workspace->analysis_data['error_code'] }}
                        — {{ $workspace->analysis_data['error_message'] ?? '' }}
                    </div>
                @endif

                @if($workspace->analysis_status === 'approved')
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-700">
                            <i class='bx bx-check-circle text-base'></i>
                            Document verified — transaction is complete
                        </span>
                        <button type="button" @click="confirmReupload = true"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50">
                            <i class='bx bx-refresh'></i> Replace Photo
                        </button>
                    </div>
                @elseif($workspace->isProcessing())
                    <div class="flex items-center gap-3.5 rounded-xl border border-amber-200/80 bg-amber-50/60 p-4 text-sm text-amber-900" wire:poll.3s>
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
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 text-xs text-slate-500">
                        <h4 class="font-semibold text-slate-700">Verification Photo Upload</h4>
                        <p class="mt-1">This unlocks once the student's appointment is marked as <strong class="text-slate-700">attended</strong>.</p>
                    </div>
                @else
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/40 p-4">
                        <h4 class="text-sm font-semibold text-slate-900">Upload Final Document Photo</h4>
                        <p class="mt-0.5 text-xs text-slate-500">Upload a clear photo of the document processed for this student to complete the transaction.</p>

                        @if($workspace->analysis_status === 'rejected' && $workspace->rejection_reason)
                            @php $isTechnicalFailure = !empty($workspace->analysis_data['error_code'] ?? null); @endphp
                            <div class="mt-3 flex items-start gap-2.5 rounded-xl border p-3 text-xs {{ $isTechnicalFailure ? 'border-blue-200 bg-blue-50/70 text-blue-800' : 'border-rose-200 bg-rose-50/70 text-rose-800' }}">
                                <i class='bx {{ $isTechnicalFailure ? "bx-info-circle text-blue-500" : "bx-error-circle text-rose-500" }} text-lg shrink-0 mt-0.5'></i>
                                <div>
                                    <p class="font-semibold">{{ $isTechnicalFailure ? "Verification couldn't finish — please retry" : "Document photo not accepted" }}</p>
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
                                    try {
                                        if (el && window.html2canvas) {
                                            const canvas = await html2canvas(visibleEl, { backgroundColor: '#ffffff', scale: 1, useCORS: true });
                                            const dataUrl = canvas.toDataURL('image/jpeg', 0.85);
                                            await $wire.set('referenceImageBase64', dataUrl);
                                        }
                                    } catch (e) {
                                        console.warn('Could not capture preview frame', e);
                                    } finally {
                                        this.capturing = false;
                                        $wire.uploadSupportingFile();
                                    }
                                }
                            }"
                            @submit.prevent="submitUpload"
                            class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center"
                        >
                            <input type="file" wire:model="supportingFile" accept=".jpg,.jpeg,.png,.pdf"
                                class="block w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-200/60 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">

                            <button type="submit" :disabled="capturing" wire:loading.attr="disabled" wire:target="uploadSupportingFile"
                                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-[#2A57B4] px-4 py-2 text-xs font-semibold text-white shadow-sm transition-all hover:bg-[#24499A] disabled:opacity-50">
                                <span x-show="!capturing" wire:loading.remove wire:target="uploadSupportingFile">Upload File</span>
                                <span x-show="capturing" class="flex items-center gap-1.5">
                                    <i class='bx bx-loader-alt animate-spin'></i> Preparing...
                                </span>
                                <span wire:loading wire:target="uploadSupportingFile" class="flex items-center gap-1.5">
                                    <i class='bx bx-loader-alt animate-spin'></i> Uploading...
                                </span>
                            </button>
                        </form>
                        @error('supportingFile') <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            {{-- Uploaded Photo Thumbnail Widget --}}
            @if($workspace->supporting_file_path)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">
                        {{ $workspace->analysis_status === 'approved' ? 'Approved Document Attachment' : 'Uploaded Verification Photo' }}
                    </h3>

                    @if($this->supportingFileUrl)
                        <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="h-28 w-28 shrink-0 overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50">
                                @if($this->supportingFileIsImage)
                                    <img src="{{ $this->supportingFileUrl }}" class="h-full w-full object-cover transition-transform duration-300 hover:scale-105" alt="Uploaded Document">
                                @else
                                    <div class="flex h-full items-center justify-center text-slate-400">
                                        <i class='bx bx-file-blank text-3xl'></i>
                                    </div>
                                @endif
                            </div>
                            <div class="space-y-1.5">
                                <a href="{{ $this->supportingFileUrl }}" target="_blank"
                                    class="inline-flex items-center gap-1 text-sm font-semibold text-[#2A57B4] hover:underline">
                                    <span>View full dimension photo</span>
                                    <i class='bx bx-external-link text-xs'></i>
                                </a>
                                @if($workspace->isProcessing())
                                    <p class="text-xs text-amber-600">AI review currently analyzing visual features...</p>
                                @elseif($workspace->analysis_status === 'approved')
                                    <p class="text-xs text-emerald-600">Verified and accepted — record updated successfully.</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-xs text-slate-500">
                            The uploaded photo did not meet quality standards. Please submit a clearer image.
                        </p>
                    @endif
                </div>
            @endif

            {{-- AI Review Analysis Breakdown --}}
            @php $hasContentAnalysis = array_key_exists('matches_document', $workspace->analysis_data ?? []); @endphp
            @if($workspace->analysis_summary || $workspace->ocr_text || $hasContentAnalysis)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-[#2A57B4]">
                                <i class='bx bx-bot text-lg'></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900">AI Verification Summary</h3>
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
                                {{ $isApproved ? 'Passed' : 'Attention Required' }}
                            </span>
                        @endif
                    </div>

                    @if($workspace->analysis_summary)
                        <blockquote class="rounded-xl bg-slate-50/80 border-l-4 border-slate-300 p-3 text-xs italic text-slate-600">
                            "{{ $workspace->analysis_summary }}"
                        </blockquote>
                    @endif

                    @if(!empty($workspace->analysis_data))
                        @php
                            $checks = [
                                'matches_document' => 'Document layout match',
                                'readable' => 'Text legibility & clarity',
                                'fields_complete' => 'All mandatory fields populated',
                                'has_signature_or_stamp' => 'Official stamp or signature',
                                'fields_match_expected' => 'Field data matches record',
                            ];
                        @endphp
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($checks as $key => $label)
                                @php $pass = (bool) ($workspace->analysis_data[$key] ?? ($key === 'fields_match_expected')); @endphp
                                <div class="flex items-center gap-2 rounded-lg border border-slate-100 p-2 text-xs">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $pass ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                                        <i class='bx {{ $pass ? "bx-check" : "bx-x" }}'></i>
                                    </span>
                                    <span class="{{ $pass ? 'text-slate-700 font-medium' : 'text-slate-500' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if(!empty($workspace->analysis_data['missing_fields']))
                            <div class="rounded-xl border border-amber-200/60 bg-amber-50/50 p-3">
                                <p class="text-xs font-semibold text-amber-800">Incomplete Fields Detected</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($workspace->analysis_data['missing_fields'] as $field)
                                        <span class="rounded-md border border-amber-300/60 bg-white px-2 py-0.5 text-[11px] font-medium text-amber-800">{{ $field }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($workspace->analysis_data['field_matches']))
                            <div class="space-y-2">
                                <p class="text-xs font-semibold text-slate-700">Detailed Data Validation</p>
                                <div class="overflow-x-auto rounded-xl border border-slate-200/80">
                                    <table class="w-full text-left text-xs">
                                        <thead class="bg-slate-50 font-semibold text-slate-500 border-b border-slate-200">
                                            <tr>
                                                <th class="px-3 py-2">Field</th>
                                                <th class="px-3 py-2">System Record</th>
                                                <th class="px-3 py-2">Document Value</th>
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
                        <details class="group rounded-xl border border-slate-200/80 bg-slate-50/50 p-3">
                            <summary class="flex cursor-pointer items-center justify-between text-xs font-semibold text-slate-600 group-open:text-slate-900">
                                <span>View Extracted Optical Text</span>
                                <i class='bx bx-chevron-down text-base transition-transform group-open:rotate-180'></i>
                            </summary>
                            <p class="mt-2 whitespace-pre-wrap font-mono text-[11px] leading-relaxed text-slate-600 border-t border-slate-200/60 pt-2">{{ $workspace->ocr_text }}</p>
                        </details>
                    @endif
                </div>
            @endif
        </div>

        {{-- Transaction Info Sidebar --}}
        <aside class="sticky top-6 h-fit space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-bold tracking-tight text-slate-900">Transaction Info</h3>
                <div class="mt-4 space-y-3 text-xs">
                    <div>
                        <p class="font-medium text-slate-400">Created</p>
                        <p class="mt-0.5 font-semibold text-slate-700">{{ $workspace->created_at->format('F j, Y g:i A') }}</p>
                    </div>
                    @if($appointment = $workspace->appointments->firstWhere('status', 'attended'))
                        <div>
                            <p class="font-medium text-slate-400">Appointment Attended</p>
                            <p class="mt-0.5 font-semibold text-slate-700">{{ $appointment->updated_at->format('F j, Y g:i A') }}</p>
                        </div>
                    @endif
                    @if($workspace->completed_at)
                        <div>
                            <p class="font-medium text-slate-400">Transaction Completed</p>
                            <p class="mt-0.5 font-semibold text-emerald-700">{{ $workspace->completed_at->format('F j, Y g:i A') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <a href="{{ route('admin.transactions.show', $workspace->workspace_id) }}" wire:navigate
                class="flex items-center justify-center gap-1.5 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs font-semibold text-slate-600 shadow-sm hover:bg-slate-50">
                <i class='bx bx-transfer'></i>
                View Full Transaction Record
            </a>
        </aside>
    </div>

    {{-- Re-upload confirmation modal --}}
    <template x-teleport="body">
        <div x-show="confirmReupload" x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3 text-[#2A57B4]">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50">
                        <i class='bx bx-refresh text-2xl'></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Replace Verified Photo?</h3>
                </div>
                <p class="text-xs leading-relaxed text-slate-600">
                    This document was already verified. Uploading a new photo will re-run AI review and briefly mark this transaction as processing again.
                </p>
                <div class="flex justify-end gap-2.5">
                    <button @click="confirmReupload = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button @click="confirmReupload = false; $wire.set('workspace.analysis_status', 'rejected')"
                        class="rounded-xl bg-[#2A57B4] px-4 py-2 text-xs font-semibold text-white hover:bg-[#24499A]">
                        Continue
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>