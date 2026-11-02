@php
    $focus = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#2A57B4]/40 focus-visible:ring-offset-1';
    $tag = match ($status) {
        'Completed' => 'bg-emerald-600 text-white',
        'Missed' => 'bg-rose-600 text-white',
        'For Appointment', 'Pending' => 'bg-amber-500 text-white',
        default => 'bg-[#2A57B4] text-white',
    };
    $btn = 'inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 [@media(pointer:coarse)]:h-11 ' . $focus;
    $row = 'grid gap-1 py-3 sm:grid-cols-[11rem_minmax(0,1fr)] sm:gap-4';
    $dt = 'text-sm text-slate-500';
    $dd = 'min-w-0 break-words text-sm text-slate-900';
    $hasReview = $ocrText || $analysisSummary || $hasContentAnalysis || $rejectionReason;
    $tabs = ['details' => 'Details'] + ($hasReview ? ['review' => 'AI review'] : []) + ['appointment' => 'Appointment', 'history' => 'History'];
    $idLabel = '#' . str_pad((string) $workspace->workspace_id, 4, '0', STR_PAD_LEFT);
@endphp

<div class="mx-auto max-w-7xl pb-16" x-data="{ tab: 'details', page: @js($generatedPdfUrl || $templatePreviewUrl ? 'form' : 'photo') }">
    {{-- Title bar: back, the form and its number, then the actions --}}
    <div class="mb-5 flex flex-wrap items-center gap-3">
        <a href="{{ $backUrl ?? session('transactions.back', route('admin.transactions.index')) }}" wire:navigate aria-label="{{ $backUrl ? 'Back to the upload' : 'Back to transaction records' }}" title="Back to transaction records"
           class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 shadow-sm hover:bg-slate-50 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
            <i class="bx bx-arrow-back text-lg" aria-hidden="true"></i>
        </a>
        <div class="min-w-0 flex-1">
            <h1 class="flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xl font-semibold tracking-[-0.015em] text-slate-900 sm:text-2xl">
                <span class="min-w-0">{{ $workspace->template?->name ?: 'Untitled template' }}</span>
                <span class="rounded-md bg-[#1E4090] px-1.5 py-0.5 font-mono text-xs font-medium text-white">{{ $idLabel }}</span>
                <span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $tag }}">{{ $status }}</span>
            </h1>
            <p class="mt-0.5 truncate text-sm text-slate-500">{{ $studentName }} · {{ $workspace->user?->student_number ?: 'No student number' }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if ($supportingFileUrl)
                <a href="{{ $supportingFileUrl }}" download class="{{ $btn }}"><i class="bx bx-image text-base" aria-hidden="true"></i> Uploaded photo</a>
            @endif
            @if ($workspace->generated_pdf_path)
                <a href="{{ route('admin.transactions.download-pdf', $workspace) }}"
                   class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3.5 text-sm font-medium text-white shadow-sm hover:bg-[#24499A] [@media(pointer:coarse)]:h-11 {{ $focus }}">
                    <i class="bx bx-download text-base" aria-hidden="true"></i> Download form
                </a>
            @endif
        </div>
    </div>

    @if ($workspace->isProcessing())
        <div class="mb-4 flex items-center gap-3 rounded-lg bg-[#e6e0f5] px-4 py-3 text-sm text-[#3d2a78]" wire:poll.3s>
            <span class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-[#5b3fa0] border-t-transparent"></span>
            <span class="font-medium">{{ $processingStage ?: 'AI review in progress…' }}</span>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
        {{-- Left: the record --}}
        <div class="min-w-0">
            <div class="mb-1 flex gap-1 overflow-x-auto border-b border-slate-200" role="tablist" aria-label="Record">
                @foreach ($tabs as $key => $label)
                    <button type="button" role="tab" @click="tab = '{{ $key }}'" :aria-selected="tab === '{{ $key }}'"
                            class="-mb-px shrink-0 border-b-2 px-3 py-2.5 text-sm font-medium transition {{ $focus }}"
                            :class="tab === '{{ $key }}' ? 'border-[#2A57B4] text-[#2A57B4]' : 'border-transparent text-slate-500 hover:text-slate-800'">{{ $label }}</button>
                @endforeach
            </div>

            <div x-show="tab === 'details'" role="tabpanel">
                <dl class="divide-y divide-slate-100">
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Student</dt>
                        <dd class="{{ $dd }} flex items-center gap-2.5">
                            <x-dm.avatar :user="$workspace->user" :name="$studentName" class="h-8 w-8 text-[11px]" />
                            <span class="min-w-0"><span class="block truncate font-medium">{{ $studentName }}</span><span class="block truncate text-xs text-slate-500">{{ $workspace->user?->student_number ?: 'No student number' }}</span></span>
                        </dd>
                    </div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Program</dt><dd class="{{ $dd }}">{{ $programLabel }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Document type</dt><dd class="{{ $dd }}">{{ $workspace->template?->name ?: 'Untitled template' }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Transaction no.</dt><dd class="{{ $dd }} font-mono">{{ $idLabel }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Status</dt><dd class="{{ $dd }}"><span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $tag }}">{{ $status }}</span></dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Mode</dt><dd class="{{ $dd }}">Paper</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Submitted</dt><dd class="{{ $dd }} tabular-nums">{{ optional($workspace->created_at)->format('M j, Y · g:i A') ?: 'N/A' }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Appointment</dt><dd class="{{ $dd }}">{{ $appointmentStatus }}@if ($appointment) <span class="text-slate-500">· {{ $appointmentDateLabel }}</span>@endif</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Queue no.</dt><dd class="{{ $dd }} tabular-nums">{{ $queueNumberLabel }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Generated form</dt><dd class="{{ $dd }}">{{ $workspace->generated_pdf_path ? $pdfMimeLabel . ' · ready to download' : 'Not generated yet' }}</dd></div>
                    <div class="{{ $row }}"><dt class="{{ $dt }}">Uploaded photo</dt><dd class="{{ $dd }}">{{ $supportingFileUrl ? $supportingMimeLabel . ' · reviewed by AI' : 'Nothing uploaded yet' }}</dd></div>
                </dl>
            </div>

            @if ($hasReview)
                <div x-show="tab === 'review'" x-cloak role="tabpanel" class="pt-4">

                    @if ($analysisStatus)
                        <p class="mb-4"><span class="rounded-md px-1.5 py-0.5 text-xs font-medium {{ $isDocApproved ? 'bg-emerald-600 text-white' : ($isTechnicalFailure ? 'bg-[#2A57B4] text-white' : ($analysisStatus === 'rejected' ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white')) }}">{{ $isDocApproved ? 'Approved' : ($isTechnicalFailure ? 'Review failed, retry needed' : ($analysisStatus === 'rejected' ? 'Rejected' : 'Pending review')) }}</span></p>
                    @endif

                    @if ($rejectionReason)
                        <div class="rounded-lg border px-4 py-3 text-sm {{ $isTechnicalFailure ? 'border-blue-200 bg-blue-50 text-blue-800' : 'border-red-200 bg-red-50 text-red-800' }}">
                            @if ($isTechnicalFailure)
                                <p class="font-semibold">Technical failure, not a document issue</p>
                                <p class="mt-1">{{ $rejectionReason }}</p>
                                @if (!empty($analysisData['error_code']))
                                    <p class="mt-2 text-xs opacity-75">Error code: {{ $analysisData['error_code'] }}</p>
                                @endif
                            @else
                                {{ $rejectionReason }}
                            @endif
                        </div>
                    @endif

                    @if ($analysisSummary)
                        <p class="{{ $rejectionReason ? 'mt-4' : '' }} rounded-lg bg-gray-50 p-3 text-sm italic text-gray-600">"{{ $analysisSummary }}"</p>
                    @endif

                    @if ($hasContentAnalysis)
                        @php
                            $vlmChecks = [
                                'matches_document' => 'Matches this document type',
                                'readable' => 'Photo is clear and readable',
                                'fields_complete' => 'All required fields filled in',
                                'has_signature_or_stamp' => 'Signature or stamp detected',
                                'fields_match_expected' => 'Field values match what was entered',
                            ];
                        @endphp
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach ($vlmChecks as $key => $label)
                                @php $pass = (bool) ($analysisData[$key] ?? ($key === 'fields_match_expected')); @endphp
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $pass ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                                        <i class='bx {{ $pass ? "bx-check" : "bx-x" }} text-sm'></i>
                                    </span>
                                    <span class="{{ $pass ? 'text-gray-700' : 'text-gray-500' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if (!empty($analysisData['missing_fields']))
                            <div class="mt-4">
                                <p class="text-xs font-medium text-amber-700">Missing or blank fields</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach ($analysisData['missing_fields'] as $field)
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs text-amber-700">{{ $field }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (!empty($analysisData['field_matches']))
                            <div class="mt-5">
                                <p class="mb-2 text-xs font-medium text-gray-500">Field verification</p>
                                <div class="overflow-hidden rounded-lg border border-gray-200">
                                    <div class="overflow-x-auto">
                                        <table class="w-full text-xs">
                                            <thead class="border-b border-gray-100 bg-gray-50 font-medium text-gray-500">
                                                <tr>
                                                    <th class="px-4 py-2.5 text-left">Field</th>
                                                    <th class="px-4 py-2.5 text-left">Student entered</th>
                                                    <th class="px-4 py-2.5 text-left">Found on photo</th>
                                                    <th class="px-4 py-2.5 text-center">Match</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($analysisData['field_matches'] as $fm)
                                                    <tr class="{{ ($fm['matches'] ?? true) ? '' : 'bg-rose-50/60' }}">
                                                        <td class="px-4 py-2.5 font-medium text-gray-700">{{ $fm['label'] ?? '' }}</td>
                                                        <td class="px-4 py-2.5 text-gray-600">{{ $fm['expected_value'] ?? '—' }}</td>
                                                        <td class="px-4 py-2.5 text-gray-600">{{ $fm['found_value'] ?? '—' }}</td>
                                                        <td class="px-4 py-2.5 text-center">
                                                            @if ($fm['matches'] ?? false)
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
                            </div>
                        @endif

                        @if (!empty($analysisData['authenticity_notes']))
                            <div class="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                                <i class='bx bx-info-circle mt-0.5 shrink-0'></i>
                                <span>{{ $analysisData['authenticity_notes'] }}</span>
                            </div>
                        @endif
                    @endif

                    @if ($ocrText)
                        <details class="group mt-4">
                            <summary class="cursor-pointer text-xs font-medium text-gray-500 group-open:text-gray-800">View extracted text</summary>
                            <p class="mt-2 whitespace-pre-wrap rounded-lg bg-gray-50 p-3 text-sm text-gray-600">{{ $ocrText }}</p>
                        </details>
                    @endif
                                </div>
            @endif

            <div x-show="tab === 'appointment'" x-cloak role="tabpanel">
                @if ($appointment)
                    <dl class="divide-y divide-slate-100">
                        <div class="{{ $row }}"><dt class="{{ $dt }}">Status</dt><dd class="{{ $dd }}">{{ $appointmentStatus }}</dd></div>
                        <div class="{{ $row }}"><dt class="{{ $dt }}">Date</dt><dd class="{{ $dd }}">{{ $appointmentDateLabel }}</dd></div>
                        <div class="{{ $row }}"><dt class="{{ $dt }}">Time slot</dt><dd class="{{ $dd }}">{{ $appointmentTimeLabel ?: 'Not specified' }}</dd></div>
                        <div class="{{ $row }}"><dt class="{{ $dt }}">Queue no.</dt><dd class="{{ $dd }} tabular-nums">{{ $queueNumberLabel }}</dd></div>
                    </dl>
                @else
                    <p class="py-6 text-sm text-slate-500">No appointment has been booked for this transaction yet.</p>
                @endif
            </div>

            <div x-show="tab === 'history'" x-cloak role="tabpanel" class="pt-4">
                <ol class="space-y-4">
                    @forelse ($timeline as $item)
                        @php
                            $dotClass = match ($item['tone']) {
                                'green' => 'bg-emerald-500', 'blue' => 'bg-[#2A57B4]', 'amber' => 'bg-amber-500', 'red' => 'bg-rose-500', default => 'bg-slate-300',
                            };
                        @endphp
                        <li class="relative pl-6">
                            <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full {{ $dotClass }}" aria-hidden="true"></span>
                            @unless ($loop->last)<span class="absolute left-[4px] top-5 h-[calc(100%-4px)] w-px bg-slate-200" aria-hidden="true"></span>@endunless
                            <p class="text-sm font-medium text-slate-800">{{ $item['title'] }}</p>
                            <p class="text-xs tabular-nums text-slate-500">{{ $item['date'] }}</p>
                        </li>
                    @empty
                        <li class="text-sm text-slate-500">No activity yet.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        {{-- Right: the paper itself on a grey desk; the form or the student's photo of it --}}
        <div class="min-w-0 lg:sticky lg:top-4 lg:self-start">
            @if ($supportingFileUrl && ($generatedPdfUrl || $templatePreviewUrl))
                <div class="mb-2 inline-flex rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="Show">
                    <button type="button" @click="page = 'form'" :aria-pressed="page === 'form'" class="h-8 rounded-md px-3 text-sm font-medium" :class="page === 'form' ? 'bg-white text-[#2A57B4] shadow-sm ring-1 ring-slate-200' : 'text-slate-500'">Form</button>
                    <button type="button" @click="page = 'photo'" :aria-pressed="page === 'photo'" class="h-8 rounded-md px-3 text-sm font-medium" :class="page === 'photo' ? 'bg-white text-[#2A57B4] shadow-sm ring-1 ring-slate-200' : 'text-slate-500'">Uploaded photo</button>
                </div>
            @endif
            <div class="overflow-hidden rounded-xl bg-slate-200/80 p-2 sm:p-3">
                <div x-show="page === 'form'">
                    @if ($generatedPdfUrl)
                        <iframe src="{{ $generatedPdfUrl }}" title="Generated form" class="h-[75vh] w-full rounded-lg border-0 bg-white"></iframe>
                    @elseif ($templatePreviewUrl)
                        <img src="{{ $templatePreviewUrl }}" alt="The form" class="mx-auto w-full rounded-lg bg-white object-contain shadow-sm">
                    @endif
                </div>
                <div x-show="page === 'photo'" x-cloak>
                    @if ($supportingFileUrl && $supportingFileIsImage)
                        <img src="{{ $supportingFileUrl }}" alt="The student's uploaded photo" class="mx-auto max-h-[75vh] w-full rounded-lg bg-white object-contain shadow-sm">
                    @elseif ($supportingFileUrl)
                        <iframe src="{{ $supportingFileUrl }}" title="Uploaded file" class="h-[75vh] w-full rounded-lg border-0 bg-white"></iframe>
                    @else
                        <div class="flex aspect-[842/1000] flex-col items-center justify-center rounded-lg bg-white text-slate-400">
                            <i class="bx bx-file text-5xl" aria-hidden="true"></i>
                            <p class="mt-2 text-sm">Nothing to preview yet</p>
                        </div>
                    @endif
                </div>
            </div>
            @if ($generatedPdfUrl || $supportingFileUrl)
                <a :href="page === 'form' ? @js($generatedPdfUrl ?: $templatePreviewUrl) : @js($supportingFileUrl)" target="_blank" rel="noopener"
                   class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#2A57B4] hover:underline"><i class="bx bx-link-external" aria-hidden="true"></i> Open full size</a>
            @endif
        </div>
    </div>
</div>
