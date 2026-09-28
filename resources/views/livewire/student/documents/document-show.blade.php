<div x-data="{ confirmDelete: false, confirmEdit: false }" class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6">
    
    {{-- Header Section --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                    {{ $workspace->template?->name ?? 'Document Workspace' }}
                </h2>
                @if($workspace->isCompleted())
                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Completed</span>
                @elseif($workspace->isProcessing())
                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">Processing</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600 ring-1 ring-inset ring-slate-500/10">In Progress</span>
                @endif
            </div>
            <p class="mt-1 flex items-center gap-1.5 text-xs text-slate-500 sm:text-sm">
                <i class='bx bx-calendar text-slate-400'></i>
                Submitted {{ $workspace->created_at->format('F j, Y') }}
            </p>
        </div>
        
        <a href="{{ route('student.documents.index') }}" wire:navigate
            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:text-slate-900 active:scale-[0.98]">
            <i class='bx bx-left-arrow-alt text-lg'></i>
            <span>Back to documents</span>
        </a>
    </div>

    {{-- Engine Partials --}}
    @include('livewire.student.partials.preview-engine')

    {{-- Main Workspace Grid (Aligned Top) --}}
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

            {{-- Dynamic Quick Actions Bar --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                @unless($workspace->isCompleted())
                    @if(!empty($workspace->analysis_data['error_code']))
                        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50/50 p-3 text-xs text-rose-600">
                            <span class="font-semibold">Technical Detail:</span> {{ $workspace->analysis_data['error_code'] }} 
                            — {{ $workspace->analysis_data['error_message'] ?? '' }}
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2.5 sm:flex sm:flex-wrap sm:items-center">
                        <button type="button" @click="confirmEdit = true"
                            class="col-span-1 inline-flex items-center justify-center gap-1.5 rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-all hover:bg-[#24499A] active:scale-[0.98]">
                            <i class='bx bx-edit-alt text-lg'></i>
                            <span class="hidden sm:inline">Edit Document</span>
                            <span class="sm:hidden">Edit</span>
                        </button>

                        @if($workspace->generated_pdf_path)
                            <a href="{{ Storage::disk('public')->url($workspace->generated_pdf_path) }}" target="_blank"
                                class="col-span-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-[0.98]">
                                <i class='bx bx-show text-lg'></i>
                                <span class="hidden sm:inline">View PDF</span>
                                <span class="sm:hidden">View</span>
                            </a>
                            <a href="{{ route('student.workspaces.download-pdf', $workspace) }}"
                                class="col-span-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-[0.98]">
                                <i class='bx bx-download text-lg'></i>
                                <span class="hidden sm:inline">Export</span>
                                <span class="sm:hidden">Export</span>
                            </a>
                        @endif

                        @if($this->activeAppointment)
                            <a href="{{ route('student.appointments.show', $this->activeAppointment) }}" wire:navigate
                                class="col-span-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition-all hover:bg-emerald-100 active:scale-[0.98]">
                                <i class='bx bx-calendar-check text-lg'></i>
                                <span class="hidden sm:inline">View Appointment</span>
                                <span class="sm:hidden">Appt.</span>
                            </a>
                        @else
                            <a href="{{ route('student.appointments.new', ['workspace' => $workspace->workspace_id]) }}" wire:navigate
                                class="col-span-1 inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 active:scale-[0.98]">
                                <i class='bx bx-calendar-plus text-lg'></i>
                                <span class="hidden sm:inline">Set Appointment</span>
                                <span class="sm:hidden">Set Appt.</span>
                            </a>
                        @endif

                        <button type="button" @click="confirmDelete = true"
                            class="col-span-2 sm:col-span-1 sm:ml-auto inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50/50 px-4 py-2.5 text-sm font-semibold text-rose-600 transition-all hover:bg-rose-100 hover:text-rose-700 active:scale-[0.98]">
                            <i class='bx bx-trash text-lg'></i>
                            <span>Delete</span>
                        </button>
                    </div>

                    @if($workspace->isCompleted())
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <h3 class="text-sm font-semibold text-slate-900">
                                {{ $workspace->analysis_status === 'approved' ? 'Approved Document Attachment' : 'Document Verification Photo' }}
                            </h3>

                            @if($workspace->supporting_file_path && $this->supportingFileUrl)
                                <div class="mt-3 flex flex-col gap-4 sm:flex-row sm:items-center">
                                    <div class="h-28 w-28 shrink-0 overflow-hidden rounded-xl border border-slate-200/80 bg-slate-50">
                                        @if($this->supportingFileIsImage)
                                            <img src="{{ $this->supportingFileUrl }}" class="h-full w-full object-cover" alt="Document photo">
                                        @else
                                            <div class="flex h-full items-center justify-center text-slate-400">
                                                <i class='bx bx-file-blank text-3xl'></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="space-y-1.5">
                                        <a href="{{ $this->supportingFileUrl }}" target="_blank" class="inline-flex items-center gap-1 text-sm font-semibold text-[#2A57B4] hover:underline">
                                            <span>View full dimension photo</span>
                                            <i class='bx bx-external-link text-xs'></i>
                                        </a>
                                        @if($workspace->isProcessing())
                                            <p class="text-xs text-amber-600">AI review currently analyzing visual features...</p>
                                        @elseif($workspace->analysis_status === 'approved')
                                            <p class="text-xs text-emerald-600">Verified and accepted — record updated successfully.</p>
                                        @elseif($workspace->analysis_status === 'rejected')
                                            <p class="text-xs text-rose-600">The office's photo didn't pass review and will be re-uploaded.</p>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <p class="mt-2 text-xs text-slate-500">
                                    Your transaction is complete. The office will upload a verification photo of the processed document here shortly.
                                </p>
                            @endif
                        </div>
                    @endif
                @else
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs font-semibold text-slate-500">Document finalized and approved</span>
                        <div class="flex items-center gap-2">
                            <a href="{{ Storage::disk('public')->url($workspace->generated_pdf_path) }}" target="_blank"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                <i class='bx bx-show text-lg'></i> View PDF
                            </a>
                            <a href="{{ route('student.workspaces.download-pdf', $workspace) }}"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[#24499A]">
                                <i class='bx bx-download text-lg'></i> Download PDF
                            </a>
                        </div>
                    </div>
                @endunless
            </div>

            {{-- Uploaded Photo Thumbnail Widget --}}
            @if($workspace->supporting_file_path)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">
                        {{ $workspace->isCompleted() ? 'Approved Document Attachment' : 'Uploaded Supporting Image' }}
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
                                @elseif($workspace->isCompleted())
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

        {{-- Timeline Sidebar Column (Aligned to top of Preview) --}}
        <aside class="sticky top-6 h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold tracking-tight text-slate-900">Activity Timeline</h3>
            <div class="mt-5 space-y-6">
                @foreach($this->timeline as $item)
                    @php
                        $dot = match($item['tone']) {
                            'green' => 'bg-emerald-500 ring-emerald-100', 
                            'blue' => 'bg-[#2A57B4] ring-blue-100',
                            'amber' => 'bg-amber-500 ring-amber-100', 
                            'red' => 'bg-rose-500 ring-rose-100', 
                            default => 'bg-slate-300 ring-slate-100',
                        };
                    @endphp
                    <div class="relative pl-6">
                        <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full ring-4 {{ $dot }}"></span>
                        @unless($loop->last)
                            <span class="absolute left-[4px] top-4 h-[calc(100%+12px)] w-px bg-slate-200"></span>
                        @endunless

                        @if($item['section'] === 'appointment')
                            <span class="mb-1 inline-block rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-[#2A57B4]">
                                Appointment
                            </span>
                        @endif

                        <p class="text-xs font-semibold text-slate-800">{{ $item['title'] }}</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $item['date'] }}</p>
                    </div>
                @endforeach
            </div>
        </aside>

    </div>

    {{-- Modals --}}
    <template x-teleport="body">
        <div x-show="confirmDelete" x-cloak 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl space-y-4">
                <div class="flex items-center gap-3 text-rose-600">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50">
                        <i class='bx bx-trash text-2xl'></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Delete Document</h3>
                </div>
                <p class="text-xs text-slate-600 leading-relaxed">Are you sure you want to remove this document workspace? This operation cannot be reversed.</p>
                <div class="flex justify-end gap-2.5">
                    <button @click="confirmDelete = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button wire:click="deleteWorkspace" class="rounded-xl bg-rose-600 px-4 py-2 text-xs font-semibold text-white hover:bg-rose-700">Delete</button>
                </div>
            </div>
        </div>
    </template>

    <template x-teleport="body">
        <div x-show="confirmEdit" x-cloak 
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
                        <i class='bx bx-edit text-2xl'></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">Edit Document</h3>
                </div>
                <p class="text-xs leading-relaxed text-slate-600">
                    Any dynamic date fields set to <strong class="text-slate-800">"today's date"</strong> will update automatically to the current date when re-saved.
                </p>
                <div class="flex justify-end gap-2.5">
                    <button @click="confirmEdit = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <a href="{{ route('student.new-transaction.edit', ['template' => $workspace->template_id, 'workspace' => $workspace->workspace_id]) }}"
                        wire:navigate
                        class="rounded-xl bg-[#2A57B4] px-4 py-2 text-xs font-semibold text-white hover:bg-[#24499A]">
                        Continue to Edit
                    </a>
                </div>
            </div>
        </div>
    </template>

</div>