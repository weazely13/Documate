@php
    $transactionStatusClass = match ($status) {
        'Completed' => 'text-green-600',
        'For Appointment' => 'text-amber-500',
        'Waiting Upload' => 'text-blue-600',
        'Missed' => 'text-red-600',
        default => 'text-orange-500',
    };
    $appointmentStatusClass = match ($appointmentStatus) {
        'Attended' => 'text-green-600',
        'Scheduled' => 'text-blue-600',
        'Missed' => 'text-red-600',
        default => 'text-amber-500',
    };
@endphp

<div class="space-y-5">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $studentName }} - {{ $workspace->template?->name ?: 'Document' }}</h1>
            <p class="mt-1 text-base text-slate-500">{{ $workspace->user?->student_number ?: 'No student number' }}</p>
        </div>

        <a href="{{ route('admin.transactions.index') }}"
           wire:navigate
           class="inline-flex items-center gap-2 rounded-[10px] bg-[#f4a61d] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#e19511]">
            <i class='bx bx-chevron-left text-lg'></i>
            <span>Back</span>
        </a>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_190px]">
        <div class="overflow-hidden rounded-[16px] border border-[#bfc8d8] bg-white">
            <div class="grid border-b border-[#cfd7e4] md:grid-cols-3">
                <div class="border-b border-[#cfd7e4] px-5 py-4 md:border-b-0 md:border-r">
                    <p class="text-sm text-slate-500">Appointment Status :</p>
                    <p class="mt-1 text-lg font-semibold {{ $appointmentStatusClass }}">{{ $appointmentStatus }}</p>

                    <p class="mt-4 text-sm text-slate-500">Transaction Status :</p>
                    <p class="mt-1 text-lg font-semibold {{ $transactionStatusClass }}">{{ $status }}</p>
                </div>

                <div class="border-b border-[#cfd7e4] px-5 py-4 md:border-b-0 md:border-r">
                    <p class="text-sm text-slate-500">Transaction ID :</p>
                    <p class="mt-2 text-[34px] font-extrabold leading-none text-slate-900">{{ str_pad((string) $workspace->workspace_id, 3, '0', STR_PAD_LEFT) }}</p>
                </div>

                <div class="px-5 py-4">
                    <p class="text-sm text-slate-500">Created :</p>
                    <p class="mt-2 text-base font-semibold text-slate-900">{{ optional($workspace->created_at)->format('F j, Y') ?: 'N/A' }}</p>
                    <p class="text-sm text-slate-500">{{ optional($workspace->created_at)->format('g:i A') ?: '' }}</p>
                </div>
            </div>

            <div class="grid border-b border-[#cfd7e4] md:grid-cols-2">
                <div class="border-b border-[#cfd7e4] px-5 py-4 md:border-b-0 md:border-r">
                    <h2 class="text-lg font-bold text-slate-900">Transaction Information</h2>
                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                        <p>{{ $workspace->template?->name ?: 'Untitled template' }}</p>
                        <p>Workspace submission</p>
                        <p>{{ optional($workspace->created_at)->format('F j, Y') ?: 'N/A' }}</p>
                    </div>
                </div>

                <div class="px-5 py-4">
                    <h2 class="text-lg font-bold text-slate-900">Student Information</h2>
                    <div class="mt-3 space-y-1 text-sm text-slate-600">
                        <p>{{ $studentName }}</p>
                        <p>{{ $workspace->user?->student_number ?: 'No student number' }}</p>
                        <p>{{ $programLabel }}</p>
                    </div>
                </div>
            </div>

            <div class="border-b border-[#cfd7e4] px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900">Documents</h2>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-[14px] border border-[#d9dfeb] p-4">
                        <div class="flex gap-4">
                            <div class="h-24 w-20 overflow-hidden rounded-[8px] border border-slate-200 bg-slate-50">
                                @if($templatePreviewUrl)
                                    <img src="{{ $templatePreviewUrl }}" alt="Generated document preview" class="h-full w-full object-cover">
                                @else
                                    <div class="flex h-full items-center justify-center text-[10px] text-slate-400">No Preview</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $pdfMimeLabel }}</p>
                                <p class="mt-1 text-base font-semibold text-slate-900">Generated {{ $workspace->template?->name ?: 'Document' }}</p>
                                <p class="mt-1 text-sm text-slate-500">Saved PDF export</p>
                                <p class="text-sm text-slate-500">{{ $workspace->generated_pdf_path ? 'Available for download and preview' : 'No generated PDF yet' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ $workspace->generated_pdf_path ? route('admin.transactions.download-pdf', $workspace) : '#' }}"
                               class="inline-flex min-w-[100px] items-center justify-center rounded-[8px] bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#214795] {{ $workspace->generated_pdf_path ? '' : 'pointer-events-none opacity-50' }}">
                                Download
                            </a>
                            <a href="{{ $generatedPdfUrl ?: '#' }}"
                               target="_blank"
                               class="inline-flex min-w-[100px] items-center justify-center rounded-[8px] border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[#2A57B4] hover:text-[#2A57B4] {{ $generatedPdfUrl ? '' : 'pointer-events-none opacity-50' }}">
                                Preview
                            </a>
                        </div>
                    </div>

                    <div class="rounded-[14px] border border-[#d9dfeb] p-4">
                        <div class="flex gap-4">
                            <div class="h-24 w-20 overflow-hidden rounded-[8px] border border-slate-200 bg-slate-50">
                                @if($supportingFileUrl && $supportingFileIsImage)
                                    <img src="{{ $supportingFileUrl }}" alt="Supporting document preview" class="h-full w-full object-cover">
                                @elseif($supportingFileUrl)
                                    <div class="flex h-full flex-col items-center justify-center gap-1 text-slate-400">
                                        <i class='bx bx-file text-2xl'></i>
                                        <span class="text-[10px] font-semibold">{{ $supportingMimeLabel }}</span>
                                    </div>
                                @else
                                    <div class="flex h-full items-center justify-center text-[10px] text-slate-400">No Upload</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">{{ $supportingMimeLabel }}</p>
                                <p class="mt-1 text-base font-semibold text-slate-900">Supporting Document</p>
                                <p class="mt-1 text-sm text-slate-500">Latest uploaded verification file</p>
                                <p class="text-sm text-slate-500">{{ $supportingFileUrl ? 'Content extracted for OCR' : 'No supporting file uploaded yet' }}</p>
                            </div>
                        </div>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ $workspace->user?->latestVerification?->e_slip_path ? route('admin.transactions.download-verification', $workspace->user->latestVerification) : '#' }}"
                               class="inline-flex min-w-[100px] items-center justify-center rounded-[8px] bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#214795] {{ $workspace->user?->latestVerification?->e_slip_path ? '' : 'pointer-events-none opacity-50' }}">
                                Download
                            </a>
                            <a href="{{ $supportingFileUrl ?: '#' }}"
                               target="_blank"
                               class="inline-flex min-w-[100px] items-center justify-center rounded-[8px] border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-[#2A57B4] hover:text-[#2A57B4] {{ $supportingFileUrl ? '' : 'pointer-events-none opacity-50' }}">
                                Preview
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-5 py-4">
                <h2 class="text-lg font-bold text-slate-900">Appointment Details</h2>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2 text-sm text-slate-500">
                        <p>Date :</p>
                        <p>Session :</p>
                        <p>Queue Number :</p>
                        <p>Status :</p>
                    </div>
                    <div class="space-y-2 text-right text-sm text-slate-700">
                        <p>{{ $appointmentDate }}</p>
                        <p>{{ $sessionLabel }}</p>
                        <p>{{ str_pad((string) $workspace->workspace_id, 2, '0', STR_PAD_LEFT) }}</p>
                        <p class="font-semibold {{ $appointmentStatusClass }}">{{ $appointmentStatus }}</p>
                    </div>
                </div>
            </div>
        </div>

        <aside class="rounded-[16px] border border-[#bfc8d8] bg-white px-4 py-4">
            <h2 class="text-lg font-bold text-slate-900">Timeline Overview</h2>
            <p class="text-xs text-slate-400">Current timeline</p>

            <div class="mt-4 space-y-4">
                @foreach($timeline as $item)
                    @php
                        $dotClass = match ($item['tone']) {
                            'green' => 'bg-green-500',
                            'blue' => 'bg-blue-500',
                            'amber' => 'bg-amber-500',
                            'red' => 'bg-red-500',
                            default => 'bg-slate-400',
                        };
                    @endphp
                    <div class="relative pl-7">
                        <span class="absolute left-0 top-1.5 h-3 w-3 rounded-full {{ $dotClass }}"></span>
                        <span class="absolute left-[5px] top-5 h-[calc(100%+8px)] w-px bg-slate-200 {{ $loop->last ? 'hidden' : '' }}"></span>
                        <p class="text-sm font-medium text-slate-800">{{ $item['title'] }}</p>
                        <p class="text-xs text-slate-400">{{ $item['date'] }}</p>
                    </div>
                @endforeach
            </div>
        </aside>
    </div>
</div>
