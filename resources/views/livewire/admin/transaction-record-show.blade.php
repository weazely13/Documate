@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');

    $statusStyles = [
        'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'For Appointment' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'Waiting Upload' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'Processing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'Missed' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'Pending' => 'bg-orange-50 text-orange-700 ring-orange-200',
    ];
    $appointmentStyles = [
        'Attended' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'Scheduled' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'Pending Approval' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'Rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'Missed' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'No Appointment' => 'bg-gray-100 text-gray-500 ring-gray-200',
    ];
    $txStyle = $statusStyles[$status] ?? $statusStyles['Pending'];
    $apptStyle = $appointmentStyles[$appointmentStatus] ?? $appointmentStyles['No Appointment'];

    $btnPrimary = 'inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2.5 text-xs font-semibold text-white transition hover:bg-blue-700 sm:py-2';
    $btnSecondary = 'inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-50 sm:py-2';
    $kpi = 'rounded-xl border border-gray-200 bg-white p-4 shadow-sm';
@endphp

<div>
    <x-admin.page-header
        :title="$studentName"
        :description="($workspace->template?->name ?: 'Document') . ' · ' . ($workspace->user?->student_number ?: 'No student number')"
        :crumbs="['Admin' => $homeUrl, 'Transactions' => route('admin.transactions.index'), 'Transaction #' . str_pad((string) $workspace->workspace_id, 4, '0', STR_PAD_LEFT) => null]">
        <x-slot:actions>
            <a href="{{ route('admin.transactions.index') }}" wire:navigate
               class="inline-flex w-full items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 sm:w-auto sm:py-2">
                <i class='bx bx-arrow-back'></i> Back to records
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Summary strip --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="{{ $kpi }}">
            <p class="text-xs text-gray-500">Transaction status</p>
            <span class="mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $txStyle }}">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $status }}
            </span>
        </div>
        <div class="{{ $kpi }}">
            <p class="text-xs text-gray-500">Appointment</p>
            <span class="mt-2 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $apptStyle }}">{{ $appointmentStatus }}</span>
        </div>
        <div class="{{ $kpi }}">
            <p class="text-xs text-gray-500">Created</p>
            <p class="mt-2 text-sm font-semibold text-gray-900">{{ optional($workspace->created_at)->format('M j, Y') ?: 'N/A' }}</p>
            <p class="text-xs text-gray-400">{{ optional($workspace->created_at)->format('g:i A') }}</p>
        </div>
        <div class="{{ $kpi }}">
            <p class="text-xs text-gray-500">Queue no.</p>
            <p class="mt-1.5 text-xl font-semibold text-gray-900">{{ $queueNumberLabel }}</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        <div class="min-w-0 space-y-6">

            {{-- Transaction & student --}}
            <x-admin.section title="Transaction details">
                <dl class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl bg-gray-50/70 p-4 text-sm sm:grid-cols-2">
                    <div class="min-w-0">
                        <dt class="mb-0.5 text-xs text-gray-400">Document</dt>
                        <dd class="truncate font-medium text-gray-800" title="{{ $workspace->template?->name }}">{{ $workspace->template?->name ?: 'Untitled template' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="mb-0.5 text-xs text-gray-400">Submitted</dt>
                        <dd class="font-medium text-gray-800">{{ optional($workspace->created_at)->format('F j, Y \a\t g:i A') ?: 'N/A' }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="mb-0.5 text-xs text-gray-400">Student</dt>
                        <dd class="truncate font-medium text-gray-800" title="{{ $studentName }}">{{ $studentName }}</dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="mb-0.5 text-xs text-gray-400">Student no.</dt>
                        <dd class="font-medium text-gray-800">{{ $workspace->user?->student_number ?: 'No student number' }}</dd>
                    </div>
                    <div class="min-w-0 sm:col-span-2">
                        <dt class="mb-0.5 text-xs text-gray-400">Program</dt>
                        <dd class="font-medium text-gray-800">{{ $programLabel }}</dd>
                    </div>
                </dl>
            </x-admin.section>

            {{-- Documents --}}
            <x-admin.section title="Documents" description="Generated form and the student's uploaded photo.">
                <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <div class="flex gap-4">
                            <div class="h-24 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                @if ($templatePreviewUrl)
                                    <img src="{{ $templatePreviewUrl }}" alt="Generated document preview" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-[10px] text-gray-400">No preview</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-medium text-gray-400">{{ $pdfMimeLabel }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">Generated document</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $workspace->generated_pdf_path ? 'Available for download' : 'No generated PDF yet' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="{{ $workspace->generated_pdf_path ? route('admin.transactions.download-pdf', $workspace) : '#' }}"
                               class="{{ $btnPrimary }} {{ $workspace->generated_pdf_path ? '' : 'pointer-events-none opacity-40' }}">
                                <i class='bx bx-download'></i> Download
                            </a>
                            <a href="{{ $generatedPdfUrl ?: '#' }}" target="_blank"
                               class="{{ $btnSecondary }} {{ $generatedPdfUrl ? '' : 'pointer-events-none opacity-40' }}">
                                <i class='bx bx-show'></i> Preview
                            </a>
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4">
                        <div class="flex gap-4">
                            <div class="h-24 w-20 shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                                @if ($supportingFileUrl && $supportingFileIsImage)
                                    <img src="{{ $supportingFileUrl }}" alt="Supporting document preview" class="h-full w-full object-cover">
                                @elseif ($supportingFileUrl)
                                    <div class="flex h-full flex-col items-center justify-center gap-1 text-gray-400">
                                        <i class='bx bx-file text-xl'></i>
                                        <span class="text-[9px] font-semibold">{{ $supportingMimeLabel }}</span>
                                    </div>
                                @else
                                    <div class="flex h-full items-center justify-center text-[10px] text-gray-400">No upload</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-medium text-gray-400">{{ $supportingMimeLabel }}</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">Uploaded photo</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $supportingFileUrl ? 'Reviewed by AI' : 'No file uploaded yet' }}</p>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <a href="{{ $supportingFileUrl ?: '#' }}" download
                               class="{{ $btnPrimary }} {{ $supportingFileUrl ? '' : 'pointer-events-none opacity-40' }}">
                                <i class='bx bx-download'></i> Download
                            </a>
                            <a href="{{ $supportingFileUrl ?: '#' }}" target="_blank"
                               class="{{ $btnSecondary }} {{ $supportingFileUrl ? '' : 'pointer-events-none opacity-40' }}">
                                <i class='bx bx-show'></i> Preview
                            </a>
                        </div>
                    </div>
                </div>
            </x-admin.section>

            {{-- Processing indicator --}}
            @if ($workspace->isProcessing())
                <div class="flex items-center gap-3 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800" wire:poll.3s>
                    <span class="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-indigo-500 border-t-transparent"></span>
                    <span class="font-medium">{{ $processingStage ?: 'AI review in progress…' }}</span>
                </div>
            @endif

            {{-- AI Document Review --}}
            @if ($ocrText || $analysisSummary || $hasContentAnalysis || $rejectionReason)
                <x-admin.section title="AI document review">
                    <x-slot:actions>
                        @if ($analysisStatus)
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1
                                {{ $isDocApproved ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
                                    : ($isTechnicalFailure ? 'bg-blue-50 text-blue-700 ring-blue-200'
                                    : ($analysisStatus === 'rejected' ? 'bg-rose-50 text-rose-700 ring-rose-200' : 'bg-amber-50 text-amber-700 ring-amber-200')) }}">
                                {{ $isDocApproved ? 'Approved' : ($isTechnicalFailure ? 'Review failed, retry needed' : ($analysisStatus === 'rejected' ? 'Rejected' : 'Pending review')) }}
                            </span>
                        @endif
                    </x-slot:actions>

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
                </x-admin.section>
            @endif

            {{-- Appointment --}}
            <x-admin.section title="Appointment details">
                @if ($appointment)
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-4 rounded-xl bg-gray-50/70 p-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="mb-0.5 text-xs text-gray-400">Date</dt>
                            <dd class="font-medium text-gray-800">{{ $appointmentDateLabel }}</dd>
                        </div>
                        <div>
                            <dt class="mb-0.5 text-xs text-gray-400">Time slot</dt>
                            <dd class="font-medium text-gray-800">{{ $appointmentTimeLabel ?: 'Not specified' }}</dd>
                        </div>
                        <div>
                            <dt class="mb-0.5 text-xs text-gray-400">Queue number</dt>
                            <dd class="font-medium text-gray-800">{{ $queueNumberLabel }}</dd>
                        </div>
                        <div>
                            <dt class="mb-1 text-xs text-gray-400">Status</dt>
                            <dd><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $apptStyle }}">{{ $appointmentStatus }}</span></dd>
                        </div>
                    </dl>
                @else
                    <p class="rounded-xl bg-gray-50/70 p-4 text-sm text-gray-400">No appointment has been booked for this transaction yet.</p>
                @endif
            </x-admin.section>
        </div>

        {{-- Timeline --}}
        <aside class="order-first h-fit xl:order-none xl:sticky xl:top-6">
            <x-admin.section title="Timeline" description="Newest first">
                <div class="space-y-5">
                    @forelse ($timeline as $item)
                        @php
                            $dotClass = match ($item['tone']) {
                                'green' => 'bg-emerald-500',
                                'blue' => 'bg-blue-500',
                                'amber' => 'bg-amber-500',
                                'red' => 'bg-rose-500',
                                default => 'bg-gray-400',
                            };
                        @endphp
                        <div class="relative pl-7">
                            <span class="absolute left-0 top-1 h-3 w-3 rounded-full {{ $dotClass }} ring-4 ring-white"></span>
                            @unless ($loop->last)
                                <span class="absolute left-[5px] top-4 h-[calc(100%+12px)] w-px bg-gray-200"></span>
                            @endunless
                            <p class="text-sm font-medium leading-tight text-gray-800">{{ $item['title'] }}</p>
                            <p class="mt-0.5 text-xs text-gray-400">{{ $item['date'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No activity yet.</p>
                    @endforelse
                </div>
            </x-admin.section>
        </aside>
    </div>
</div>