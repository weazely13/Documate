@php
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $btnBase = 'inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $btnPrimary = $btnBase . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $btnSecondary = $btnBase . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';

    $done = $workspace->isCompleted();
    $status = $workspace->displayStatus();
    $pill = match ($status['tone']) {
        'green' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
        'red' => 'bg-rose-50 text-rose-800 ring-rose-200',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
    $title = $workspace->template?->name
        ? \Illuminate\Support\Str::title(str_replace('_', ' ', $workspace->template->name))
        : 'Document';
    $pdfUrl = $workspace->generated_pdf_path ? Storage::disk('public')->url($workspace->generated_pdf_path) : null;
    $canvasW = $this->canvas['width'] ?? 794;
    $canvasH = $this->canvas['height'] ?? 1123;
    $hasContentAnalysis = array_key_exists('matches_document', $workspace->analysis_data ?? []);
@endphp

{{-- A paper form the student filled in: the page itself, what to do with it, and what has happened so far.
     Phones: header, actions, page, review, timeline. Desktop: the page on the left, actions and timeline on the right. --}}
<div x-data="{ confirmDelete: false, confirmEdit: false }" class="mx-auto max-w-6xl space-y-5 pb-28">

    {{-- Header --}}
    <div class="min-w-0">
        <a href="{{ route('student.documents.index') }}" wire:navigate
           class="inline-flex items-center gap-1 rounded text-sm text-slate-500 hover:text-slate-900 {{ $focus }}">
            <i class="bx bx-chevron-left text-lg" aria-hidden="true"></i> My documents
        </a>
        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <h1 class="min-w-0 break-words text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">{{ $title }}</h1>
            <span class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $pill }}">{{ $status['label'] }}</span>
        </div>
        <p class="mt-0.5 text-sm text-slate-500">Paper form · Started {{ $workspace->created_at->format('M j, Y') }}</p>
    </div>

    @include('livewire.student.partials.preview-engine')

    <div class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_320px] lg:grid-rows-[auto_1fr]">

        {{-- Actions --}}
        <section class="rounded-2xl border border-slate-200 bg-white p-4 lg:col-start-2 lg:row-start-1" aria-label="Actions">
            @if ($done)
                <p class="text-sm font-medium text-slate-900">This form is complete.</p>
                <p class="mt-0.5 text-xs text-slate-500">Download a copy for your records.</p>
                @if ($pdfUrl)
                    <div class="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-1">
                        <a href="{{ route('student.workspaces.download-pdf', $workspace) }}" class="{{ $btnPrimary }}">
                            <i class="bx bx-download text-lg" aria-hidden="true"></i> Download PDF
                        </a>
                        <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" class="{{ $btnSecondary }}">
                            <i class="bx bx-show text-lg" aria-hidden="true"></i> View PDF
                        </a>
                    </div>
                @endif
            @else
                @if ($this->visitCompleted)
                    <p class="text-sm font-medium text-slate-900">Visit completed.</p>
                    <p class="mt-0.5 text-xs text-slate-500">The VPSD personnel will upload a photo of this document for it to become an online copy.</p>
                @else
                    <p class="text-sm font-medium text-slate-900">Next: print it and bring it to the VPSD office.</p>
                    <p class="mt-0.5 text-xs text-slate-500">Book a visit so the office expects you.</p>
                @endif

                @if (! empty($workspace->analysis_data['error_code']))
                    <p class="mt-3 rounded-lg border border-rose-200 bg-rose-50 p-2.5 text-xs text-rose-700">
                        <span class="font-semibold">Technical detail:</span> {{ $workspace->analysis_data['error_code'] }}
                        {{ ! empty($workspace->analysis_data['error_message']) ? '· ' . $workspace->analysis_data['error_message'] : '' }}
                    </p>
                @endif

                <div class="mt-3 grid grid-cols-2 gap-2 lg:grid-cols-1">
                    @if ($this->activeAppointment)
                        <a href="{{ route('student.appointments.show', $this->activeAppointment) }}" wire:navigate class="{{ $btnPrimary }} col-span-2 lg:col-span-1">
                            <i class="bx bx-calendar-check text-lg" aria-hidden="true"></i> View appointment
                        </a>
                    @elseif (! $this->visitCompleted)
                        <a href="{{ route('student.appointments.new', ['workspace' => $workspace->workspace_id]) }}" wire:navigate class="{{ $btnPrimary }} col-span-2 lg:col-span-1">
                            <i class="bx bx-calendar-plus text-lg" aria-hidden="true"></i> Book an appointment
                        </a>
                    @endif

                    @if ($pdfUrl)
                        <a href="{{ route('student.workspaces.download-pdf', $workspace) }}" class="{{ $btnSecondary }}">
                            <i class="bx bx-download text-lg" aria-hidden="true"></i> Download
                        </a>
                        <a href="{{ $pdfUrl }}" target="_blank" rel="noopener" class="{{ $btnSecondary }}">
                            <i class="bx bx-show text-lg" aria-hidden="true"></i> View PDF
                        </a>
                    @endif

                    <button type="button" @click="confirmEdit = true" class="{{ $btnSecondary }}">
                        <i class="bx bx-edit-alt text-lg" aria-hidden="true"></i> Edit
                    </button>
                    <button type="button" @click="confirmDelete = true" class="{{ $btnBase }} border border-rose-200 bg-white text-rose-700 hover:bg-rose-50">
                        <i class="bx bx-trash text-lg" aria-hidden="true"></i> Delete
                    </button>
                </div>
            @endif
        </section>

        {{-- The page, plus what the office's review found --}}
        <div class="min-w-0 space-y-5 lg:col-start-1 lg:row-span-2 lg:row-start-1">

            {{-- Preview: fits the column's width at every size; "Full size" shows it 1:1 and scrolls --}}
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-[#f5f4f2]" aria-label="Document preview"
                     x-data="{
                        ...positionedPreviewEngine(@js($this->fields), @js($workspace->field_values ?? []), @js($this->systemValues)),
                        w: {{ $canvasW }}, h: {{ $canvasH }}, s: 1, full: false,
                        fit() { const a = this.$refs.frame?.clientWidth; if (a > 0) this.s = Math.min(a / this.w, 1); },
                     }"
                     x-init="$nextTick(() => fit()); new ResizeObserver(() => fit()).observe($refs.frame)">
                <div class="flex items-center justify-between gap-2 border-b border-slate-200 bg-white px-3 py-2 sm:px-4">
                    <p class="text-sm font-medium text-slate-900">Preview</p>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs tabular-nums text-slate-500" x-text="(full ? 100 : Math.round(s * 100)) + '%'"></span>
                        <button type="button" x-show="s < 1" x-cloak @click="full = !full; $nextTick(() => $refs.frame.scrollTo(0, 0))"
                                :aria-pressed="full.toString()"
                                class="inline-flex min-h-8 items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 [@media(pointer:coarse)]:min-h-10 {{ $focus }}">
                            <i class="bx text-base" :class="full ? 'bx-collapse' : 'bx-expand'" aria-hidden="true"></i>
                            <span x-text="full ? 'Fit to screen' : 'Full size'"></span>
                        </button>
                    </div>
                </div>
                <div class="p-2 sm:p-4">
                    <div x-ref="frame" class="w-full overflow-hidden" :class="full ? 'max-h-[75vh] !overflow-auto overscroll-contain' : ''">
                        <div class="relative mx-auto" :style="`width: ${full ? w : w * s}px; height: ${full ? h : h * s}px`"
                             style="width: 100%; aspect-ratio: {{ $canvasW }} / {{ $canvasH }}">
                            <div x-cloak class="absolute left-0 top-0 origin-top-left overflow-hidden bg-white shadow-sm ring-1 ring-slate-200"
                                 :style="`width: ${w}px; height: ${h}px; transform: scale(${full ? 1 : s})`">
                                @include('livewire.student.partials.preview-canvas', ['workspace' => $workspace, 'fields' => $this->fields])
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- The photo of the processed form --}}
            @if ($workspace->supporting_file_path)
                <section class="rounded-2xl border border-slate-200 bg-white p-4">
                    <h2 class="text-sm font-semibold text-slate-900">{{ $done ? 'Approved document photo' : 'Uploaded photo' }}</h2>
                    @if ($this->supportingFileUrl)
                        <div class="mt-3 flex items-center gap-4">
                            <a href="{{ $this->supportingFileUrl }}" target="_blank" rel="noopener"
                               class="h-24 w-24 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 {{ $focus }}">
                                @if ($this->supportingFileIsImage)
                                    <img src="{{ $this->supportingFileUrl }}" class="h-full w-full object-cover" alt="Photo of the document">
                                @else
                                    <span class="flex h-full items-center justify-center text-slate-400"><i class="bx bx-file-blank text-3xl" aria-hidden="true"></i></span>
                                @endif
                            </a>
                            <div class="min-w-0 space-y-1">
                                <a href="{{ $this->supportingFileUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-sm font-medium text-[#2A57B4] hover:underline">
                                    Open full photo <i class="bx bx-link-external text-xs" aria-hidden="true"></i>
                                </a>
                                @if ($workspace->isProcessing())
                                    <p class="text-xs text-amber-700">The photo is being checked.</p>
                                @elseif ($done || $workspace->analysis_status === 'approved')
                                    <p class="text-xs text-emerald-700">Checked and accepted.</p>
                                @elseif ($workspace->analysis_status === 'rejected')
                                    <p class="text-xs text-rose-700">This photo didn't pass the check. The office will upload a new one.</p>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="mt-2 text-xs text-slate-500">The photo could not be opened.</p>
                    @endif
                </section>
            @elseif ($done)
                <p class="rounded-2xl border border-slate-200 bg-white p-4 text-xs text-slate-500">
                    Your transaction is complete. The office will add a photo of the processed document here.
                </p>
            @endif

            {{-- What the AI check found --}}
            @if ($workspace->analysis_summary || $workspace->ocr_text || $hasContentAnalysis)
                <section class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#f0f4fb] text-[#2A57B4]" aria-hidden="true"><i class="bx bx-bot text-lg"></i></span>
                            <h2 class="text-sm font-semibold text-slate-900">Photo check</h2>
                        </div>
                        @if ($hasContentAnalysis)
                            @php
                                $isApproved = ($workspace->analysis_data['matches_document'] ?? false)
                                    && ($workspace->analysis_data['readable'] ?? false)
                                    && ($workspace->analysis_data['fields_complete'] ?? false)
                                    && ($workspace->analysis_data['has_signature_or_stamp'] ?? false)
                                    && ($workspace->analysis_data['fields_match_expected'] ?? true);
                            @endphp
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 {{ $isApproved ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-rose-50 text-rose-800 ring-rose-200' }}">
                                <i class="bx {{ $isApproved ? 'bx-check-circle' : 'bx-error-circle' }}" aria-hidden="true"></i>
                                {{ $isApproved ? 'Passed' : 'Needs attention' }}
                            </span>
                        @endif
                    </div>

                    @if ($workspace->analysis_summary)
                        <p class="rounded-lg border-l-4 border-slate-300 bg-slate-50 p-3 text-xs text-slate-600">{{ $workspace->analysis_summary }}</p>
                    @endif

                    @if (! empty($workspace->analysis_data))
                        @php
                            $checks = [
                                'matches_document' => 'Matches the form layout',
                                'readable' => 'Text is readable',
                                'fields_complete' => 'Required fields filled in',
                                'has_signature_or_stamp' => 'Has a stamp or signature',
                                'fields_match_expected' => 'Details match your record',
                            ];
                        @endphp
                        <ul class="grid gap-2 sm:grid-cols-2">
                            @foreach ($checks as $key => $label)
                                @php $pass = (bool) ($workspace->analysis_data[$key] ?? ($key === 'fields_match_expected')); @endphp
                                <li class="flex items-center gap-2 rounded-lg border border-slate-100 p-2 text-xs">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $pass ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                        <i class="bx {{ $pass ? 'bx-check' : 'bx-x' }}" aria-hidden="true"></i>
                                    </span>
                                    <span class="{{ $pass ? 'font-medium text-slate-700' : 'text-slate-500' }}">{{ $label }}<span class="sr-only">: {{ $pass ? 'passed' : 'failed' }}</span></span>
                                </li>
                            @endforeach
                        </ul>

                        @if (! empty($workspace->analysis_data['missing_fields']))
                            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <p class="text-xs font-semibold text-amber-800">Missing fields</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($workspace->analysis_data['missing_fields'] as $field)
                                        <span class="rounded-md border border-amber-300 bg-white px-2 py-0.5 text-[11px] font-medium text-amber-800">{{ $field }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (! empty($workspace->analysis_data['field_matches']))
                            <div class="space-y-2">
                                <p class="text-xs font-semibold text-slate-700">Field by field</p>
                                {{-- Phones: one card per field; wider: a table --}}
                                <ul class="space-y-2 sm:hidden">
                                    @foreach ($workspace->analysis_data['field_matches'] as $fm)
                                        <li class="rounded-lg border border-slate-200 p-2.5 text-xs">
                                            <p class="flex items-center justify-between gap-2 font-medium text-slate-800">
                                                {{ $fm['label'] ?? '-' }}
                                                <i class="bx {{ ($fm['matches'] ?? false) ? 'bx-check-circle text-emerald-600' : 'bx-x-circle text-rose-600' }} text-base" aria-label="{{ ($fm['matches'] ?? false) ? 'Matches' : 'Does not match' }}"></i>
                                            </p>
                                            <p class="mt-1 text-slate-500">Record: <span class="text-slate-700">{{ $fm['expected_value'] ?? '-' }}</span></p>
                                            <p class="text-slate-500">On the form: <span class="text-slate-700">{{ $fm['found_value'] ?? '-' }}</span></p>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="hidden overflow-x-auto rounded-lg border border-slate-200 sm:block">
                                    <table class="w-full text-left text-xs">
                                        <thead class="border-b border-slate-200 bg-slate-50 font-semibold text-slate-500">
                                            <tr>
                                                <th class="px-3 py-2">Field</th>
                                                <th class="px-3 py-2">Your record</th>
                                                <th class="px-3 py-2">On the form</th>
                                                <th class="px-3 py-2 text-center">Match</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach ($workspace->analysis_data['field_matches'] as $fm)
                                                <tr>
                                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $fm['label'] ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $fm['expected_value'] ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-slate-600">{{ $fm['found_value'] ?? '-' }}</td>
                                                    <td class="px-3 py-2 text-center">
                                                        <i class="bx {{ ($fm['matches'] ?? false) ? 'bx-check-circle text-emerald-600' : 'bx-x-circle text-rose-600' }} text-base" aria-label="{{ ($fm['matches'] ?? false) ? 'Matches' : 'Does not match' }}"></i>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif

                    @if ($workspace->ocr_text)
                        <details class="group rounded-lg border border-slate-200 bg-slate-50 p-3">
                            <summary class="flex cursor-pointer items-center justify-between text-xs font-semibold text-slate-600 group-open:text-slate-900">
                                <span>Text read from the photo</span>
                                <i class="bx bx-chevron-down text-base transition-transform group-open:rotate-180" aria-hidden="true"></i>
                            </summary>
                            <p class="mt-2 whitespace-pre-wrap break-words border-t border-slate-200 pt-2 font-mono text-[11px] leading-relaxed text-slate-600">{{ $workspace->ocr_text }}</p>
                        </details>
                    @endif
                </section>
            @endif
        </div>

        {{-- Timeline --}}
        <aside class="rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 lg:col-start-2 lg:row-start-2">
            <h2 class="text-sm font-semibold text-slate-900">Activity</h2>
            <ol class="mt-4 space-y-5">
                @foreach ($this->timeline as $item)
                    @php
                        $dot = match ($item['tone']) {
                            'green' => 'bg-emerald-500 ring-emerald-100',
                            'blue' => 'bg-[#2A57B4] ring-blue-100',
                            'amber' => 'bg-amber-500 ring-amber-100',
                            'red' => 'bg-rose-500 ring-rose-100',
                            default => 'bg-slate-300 ring-slate-100',
                        };
                    @endphp
                    <li class="relative pl-6">
                        <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full ring-4 {{ $dot }}" aria-hidden="true"></span>
                        @unless ($loop->last)
                            <span class="absolute left-[4px] top-4 h-[calc(100%+8px)] w-px bg-slate-200" aria-hidden="true"></span>
                        @endunless
                        @if ($item['section'] === 'appointment')
                            <span class="mb-1 inline-block rounded-md bg-[#f0f4fb] px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-[#2A57B4]">Appointment</span>
                        @endif
                        <p class="text-xs font-medium text-slate-800">{{ $item['title'] }}</p>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ $item['date'] }}</p>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>

    {{-- Modals: a sheet from the bottom on phones, a centred dialog wider --}}
    <template x-teleport="body">
        <div x-show="confirmDelete" x-cloak x-transition.opacity @keydown.escape.window="confirmDelete = false" @click.self="confirmDelete = false"
             class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
             role="alertdialog" aria-modal="true" aria-labelledby="ds-del-title">
            <div class="w-full max-w-sm rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                <h2 id="ds-del-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">Delete this document?</h2>
                <p class="mt-1 text-sm text-slate-500">This can't be undone. Any booked appointment for it is cancelled.</p>
                <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button type="button" @click="confirmDelete = false" class="{{ $btnSecondary }}">Keep it</button>
                    <button type="button" wire:click="deleteWorkspace" wire:loading.attr="disabled" class="{{ $btnBase }} bg-rose-600 text-white shadow-sm hover:bg-rose-700">Delete</button>
                </div>
            </div>
        </div>
    </template>

    @unless ($done)
        <template x-teleport="body">
            <div x-show="confirmEdit" x-cloak x-transition.opacity @keydown.escape.window="confirmEdit = false" @click.self="confirmEdit = false"
                 class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 sm:items-center sm:p-4"
                 role="dialog" aria-modal="true" aria-labelledby="ds-edit-title">
                <div class="w-full max-w-sm rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                    <h2 id="ds-edit-title" class="text-lg font-semibold tracking-[-0.01em] text-slate-900">Edit this document?</h2>
                    <p class="mt-1 text-sm text-slate-500">Date fields set to <strong class="font-medium text-slate-800">today's date</strong> change to the day you save it again.</p>
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" @click="confirmEdit = false" class="{{ $btnSecondary }}">Cancel</button>
                        <a href="{{ route('student.new-transaction.edit', ['template' => $workspace->template_id, 'workspace' => $workspace->workspace_id]) }}"
                           wire:navigate class="{{ $btnPrimary }}">Continue to edit</a>
                    </div>
                </div>
            </div>
        </template>
    @endunless
</div>
