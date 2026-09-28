@php
    $statusStyles = [
        'Completed' => ['text' => 'text-emerald-700', 'bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-200', 'dot' => 'bg-emerald-500'],
        'For Appointment' => ['text' => 'text-amber-700', 'bg' => 'bg-amber-50', 'ring' => 'ring-amber-200', 'dot' => 'bg-amber-500'],
        'Waiting Upload' => ['text' => 'text-blue-700', 'bg' => 'bg-blue-50', 'ring' => 'ring-blue-200', 'dot' => 'bg-blue-500'],
        'Processing' => ['text' => 'text-indigo-700', 'bg' => 'bg-indigo-50', 'ring' => 'ring-indigo-200', 'dot' => 'bg-indigo-500'],
        'Missed' => ['text' => 'text-rose-700', 'bg' => 'bg-rose-50', 'ring' => 'ring-rose-200', 'dot' => 'bg-rose-500'],
        'Pending' => ['text' => 'text-orange-700', 'bg' => 'bg-orange-50', 'ring' => 'ring-orange-200', 'dot' => 'bg-orange-500'],
    ];
    $appointmentStyles = [
        'Attended' => ['text' => 'text-emerald-700', 'bg' => 'bg-emerald-50', 'ring' => 'ring-emerald-200'],
        'Scheduled' => ['text' => 'text-blue-700', 'bg' => 'bg-blue-50', 'ring' => 'ring-blue-200'],
        'Pending Approval' => ['text' => 'text-amber-700', 'bg' => 'bg-amber-50', 'ring' => 'ring-amber-200'],
        'Rejected' => ['text' => 'text-rose-700', 'bg' => 'bg-rose-50', 'ring' => 'ring-rose-200'],
        'Missed' => ['text' => 'text-rose-700', 'bg' => 'bg-rose-50', 'ring' => 'ring-rose-200'],
        'No Appointment' => ['text' => 'text-slate-500', 'bg' => 'bg-slate-50', 'ring' => 'ring-slate-200'],
    ];
    $txStyle = $statusStyles[$status] ?? $statusStyles['Pending'];
    $apptStyle = $appointmentStyles[$appointmentStatus] ?? $appointmentStyles['No Appointment'];
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Transaction #{{ str_pad((string) $workspace->workspace_id, 4, '0', STR_PAD_LEFT) }}</p>
            <h1 class="mt-1 truncate text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                {{ $studentName }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">{{ $workspace->template?->name ?: 'Document' }} &middot; {{ $workspace->user?->student_number ?: 'No student number' }}</p>
        </div>

        <a href="{{ route('admin.transactions.index') }}"
           wire:navigate
           class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:bg-slate-50">
            <i class='bx bx-arrow-back text-lg'></i>
            <span>Back to Records</span>
        </a>
    </div>

    {{-- Status strip --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Transaction Status</p>
            <span class="mt-2 inline-flex items-center gap-1.5 rounded-full {{ $txStyle['bg'] }} {{ $txStyle['text'] }} px-2.5 py-1 text-xs font-semibold ring-1 {{ $txStyle['ring'] }}">
                <span class="h-1.5 w-1.5 rounded-full {{ $txStyle['dot'] }}"></span>
                {{ $status }}
            </span>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Appointment</p>
            <span class="mt-2 inline-flex items-center rounded-full {{ $apptStyle['bg'] }} {{ $apptStyle['text'] }} px-2.5 py-1 text-xs font-semibold ring-1 {{ $apptStyle['ring'] }}">
                {{ $appointmentStatus }}
            </span>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Created</p>
            <p class="mt-2 text-sm font-semibold text-slate-800">{{ optional($workspace->created_at)->format('M j, Y') ?: 'N/A' }}</p>
            <p class="text-xs text-slate-400">{{ optional($workspace->created_at)->format('g:i A') ?: '' }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-400">Queue No.</p>
            <p class="mt-2 text-xl font-bold text-slate-900">{{ $queueNumberLabel }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="space-y-6">
            {{-- Transaction & student info --}}
            <div class="grid gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-2">
                <div>
                    <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-400">
                        <i class='bx bx-file text-base'></i> Transaction
                    </h2>
                    <div class="mt-3 space-y-1.5">
                        <p class="text-sm font-semibold text-slate-800">{{ $workspace->template?->name ?: 'Untitled template' }}</p>
                        <p class="text-sm text-slate-500">Submitted {{ optional($workspace->created_at)->format('F j, Y \a\t g:i A') ?: 'N/A' }}</p>
                    </div>
                </div>
                <div>
                    <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-400">
                        <i class='bx bx-user text-base'></i> Student
                    </h2>
                    <div class="mt-3 space-y-1.5">
                        <p class="text-sm font-semibold text-slate-800">{{ $studentName }}</p>
                        <p class="text-sm text-slate-500">{{ $workspace->user?->student_number ?: 'No student number' }}</p>
                        <p class="text-sm text-slate-500">{{ $programLabel }}</p>
                    </div>
                </div>
            </div>

            {{-- Documents --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-400">
                    <i class='bx bx-folder-open text-base'></i> Documents
                </h2>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4 transition hover:border-slate-300">
                        <div class="flex gap-4">
                            <div class="h-24 w-20 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                @if($templatePreviewUrl)
                                    <img src="{{ $templatePreviewUrl }}" alt="Generated document preview" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-[10px] text-slate-400">No Preview</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $pdfMimeLabel }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">Generated Document</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $workspace->generated_pdf_path ? 'Available for download' : 'No generated PDF yet' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="{{ $workspace->generated_pdf_path ? route('admin.transactions.download-pdf', $workspace) : '#' }}"
                               class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#214795] {{ $workspace->generated_pdf_path ? '' : 'pointer-events-none opacity-40' }}">
                                <i class='bx bx-download'></i> Download
                            </a>
                            <a href="{{ $generatedPdfUrl ?: '#' }}" target="_blank"
                               class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 {{ $generatedPdfUrl ? '' : 'pointer-events-none opacity-40' }}">
                                <i class='bx bx-show'></i> Preview
                            </a>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4 transition hover:border-slate-300">
                        <div class="flex gap-4">
                            <div class="h-24 w-20 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                @if($supportingFileUrl && $supportingFileIsImage)
                                    <img src="{{ $supportingFileUrl }}" alt="Supporting document preview" class="h-full w-full object-cover">
                                @elseif($supportingFileUrl)
                                    <div class="flex h-full flex-col items-center justify-center gap-1 text-slate-400">
                                        <i class='bx bx-file text-xl'></i>
                                        <span class="text-[9px] font-semibold">{{ $supportingMimeLabel }}</span>
                                    </div>
                                @else
                                    <div class="flex h-full items-center justify-center text-[10px] text-slate-400">No Upload</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $supportingMimeLabel }}</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">Uploaded Photo</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $supportingFileUrl ? 'Reviewed by AI' : 'No file uploaded yet' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="{{ $supportingFileUrl ?: '#' }}" download
                               class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2 text-xs font-semibold text-white transition hover:bg-[#214795] {{ $supportingFileUrl ? '' : 'pointer-events-none opacity-40' }}">
                                <i class='bx bx-download'></i> Download
                            </a>
                            <a href="{{ $supportingFileUrl ?: '#' }}" target="_blank"
                               class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 {{ $supportingFileUrl ? '' : 'pointer-events-none opacity-40' }}">
                                <i class='bx bx-show'></i> Preview
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Processing indicator --}}
            @if($workspace->isProcessing())
                <div class="flex items-center gap-3 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-700 shadow-sm" wire:poll.3s>
                    <span class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent"></span>
                    <span class="font-medium">{{ $processingStage ?: 'AI review in progress…' }}</span>
                </div>
            @endif

            {{-- AI Document Review --}}
            @if($ocrText || $analysisSummary || $hasContentAnalysis || $rejectionReason)
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-400">
                            <i class='bx bx-scan text-base'></i> AI Document Review
                        </h2>
                        @if($analysisStatus)
                            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $isDocApproved ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : ($isTechnicalFailure ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-200' : ($analysisStatus === 'rejected' ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200')) }}">
                                {{ $isDocApproved ? 'Approved' : ($isTechnicalFailure ? 'Review failed — retry needed' : ($analysisStatus === 'rejected' ? 'Rejected' : 'Pending review')) }}
                            </span>
                        @endif
                    </div>

                    @if($rejectionReason)
                        <div class="mt-4 rounded-xl border p-3 text-sm {{ $isTechnicalFailure ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
                            @if($isTechnicalFailure)
                                <p class="font-semibold">Technical failure — not a document issue</p>
                                <p class="mt-1">{{ $rejectionReason }}</p>
                                @if(!empty($analysisData['error_code']))
                                    <p class="mt-2 text-xs opacity-75">Error code: {{ $analysisData['error_code'] }}</p>
                                @endif
                            @else
                                {{ $rejectionReason }}
                            @endif
                        </div>
                    @endif

                    @if($analysisSummary)
                        <p class="mt-4 rounded-xl bg-slate-50 p-3 text-sm italic text-slate-600">
                            "{{ $analysisSummary }}"
                        </p>
                    @endif

                    @if($hasContentAnalysis)
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
                            @foreach($vlmChecks as $key => $label)
                                @php $pass = (bool) ($analysisData[$key] ?? ($key === 'fields_match_expected')); @endphp
                                <div class="flex items-center gap-2 text-sm">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $pass ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}">
                                        <i class='bx {{ $pass ? "bx-check" : "bx-x" }} text-sm'></i>
                                    </span>
                                    <span class="{{ $pass ? 'text-slate-700' : 'text-slate-500' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>

                        @if(!empty($analysisData['missing_fields']))
                            <div class="mt-4">
                                <p class="text-xs font-semibold text-amber-700">Missing or blank fields</p>
                                <div class="mt-1.5 flex flex-wrap gap-1.5">
                                    @foreach($analysisData['missing_fields'] as $field)
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs text-amber-700">{{ $field }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if(!empty($analysisData['field_matches']))
                            <div class="mt-5">
                                <p class="text-xs font-semibold text-slate-500">Field verification</p>
                                <div class="mt-2 overflow-hidden rounded-xl border border-slate-200">
                                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                                        <thead class="bg-slate-50 text-slate-500">
                                            <tr>
                                                <th class="px-3 py-2.5 text-left font-semibold">Field</th>
                                                <th class="px-3 py-2.5 text-left font-semibold">Student entered</th>
                                                <th class="px-3 py-2.5 text-left font-semibold">Found on photo</th>
                                                <th class="px-3 py-2.5 text-center font-semibold">Match</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach($analysisData['field_matches'] as $fm)
                                                <tr class="{{ ($fm['matches'] ?? true) ? '' : 'bg-rose-50/60' }}">
                                                    <td class="px-3 py-2.5 font-medium text-slate-700">{{ $fm['label'] ?? '' }}</td>
                                                    <td class="px-3 py-2.5 text-slate-600">{{ $fm['expected_value'] ?? '—' }}</td>
                                                    <td class="px-3 py-2.5 text-slate-600">{{ $fm['found_value'] ?? '—' }}</td>
                                                    <td class="px-3 py-2.5 text-center">
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

                        @if(!empty($analysisData['authenticity_notes']))
                            <div class="mt-4 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-700">
                                <i class='bx bx-info-circle mt-0.5 shrink-0'></i>
                                <span>{{ $analysisData['authenticity_notes'] }}</span>
                            </div>
                        @endif
                    @endif

                    @if($ocrText)
                        <details class="mt-4 group">
                            <summary class="cursor-pointer text-xs font-semibold text-slate-500 group-open:text-slate-700">
                                View extracted text
                            </summary>
                            <p class="mt-2 whitespace-pre-wrap rounded-xl bg-slate-50 p-3 text-sm text-slate-600">{{ $ocrText }}</p>
                        </details>
                    @endif
                </div>
            @endif

            {{-- Appointment Details — now backed by the real appointment record --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-400">
                    <i class='bx bx-calendar-check text-base'></i> Appointment Details
                </h2>

                @if($appointment)
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm">
                                <i class='bx bx-calendar text-lg'></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium text-slate-400">Date</p>
                                <p class="text-sm font-semibold text-slate-800">{{ $appointmentDateLabel }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm">
                                <i class='bx bx-time-five text-lg'></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium text-slate-400">Time Slot</p>
                                <p class="text-sm font-semibold text-slate-800">{{ $appointmentTimeLabel ?: 'Not specified' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white text-slate-400 shadow-sm">
                                <i class='bx bx-list-ol text-lg'></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium text-slate-400">Queue Number</p>
                                <p class="text-sm font-semibold text-slate-800">{{ $queueNumberLabel }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $apptStyle['bg'] }} {{ $apptStyle['text'] }} shadow-sm">
                                <i class='bx bx-check-shield text-lg'></i>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[11px] font-medium text-slate-400">Status</p>
                                <p class="text-sm font-semibold {{ $apptStyle['text'] }}">{{ $appointmentStatus }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="mt-4 flex items-center gap-3 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 p-4 text-sm text-slate-500">
                        <i class='bx bx-calendar-x text-xl text-slate-400'></i>
                        No appointment has been booked for this transaction yet.
                    </div>
                @endif
            </div>
        </div>

        {{-- Timeline --}}
        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-slate-400">
                <i class='bx bx-history text-base'></i> Timeline
            </h2>
            <p class="mt-0.5 text-xs text-slate-400">Newest first</p>

            <div class="mt-5 space-y-5">
                @forelse($timeline as $item)
                    @php
                        $dotClass = match ($item['tone']) {
                            'green' => 'bg-emerald-500',
                            'blue' => 'bg-blue-500',
                            'amber' => 'bg-amber-500',
                            'red' => 'bg-rose-500',
                            default => 'bg-slate-400',
                        };
                    @endphp
                    <div class="relative pl-7">
                        <span class="absolute left-0 top-1 h-3 w-3 rounded-full {{ $dotClass }} ring-4 ring-white"></span>
                        @unless($loop->last)
                            <span class="absolute left-[5px] top-4 h-[calc(100%+12px)] w-px bg-slate-200"></span>
                        @endunless
                        <p class="text-sm font-medium leading-tight text-slate-800">{{ $item['title'] }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $item['date'] }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">No activity yet.</p>
                @endforelse
            </div>
        </aside>
    </div>
</div>