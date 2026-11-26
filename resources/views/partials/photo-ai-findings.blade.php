{{-- What the AI found on the photo of a printed (paper) form, the same on every page that shows it:
     the admin's upload page, Transaction Records and the student's My Documents.
     Needs: $ws (StudentDocumentWorkspace). Optional: $headingTag (default h3).
     Each page shows it only when the AI left a summary, extracted text or its checks. --}}
@php
    $data = $ws->analysis_data ?? [];
    $hasContent = array_key_exists('matches_document', $data);
    $headingTag = $headingTag ?? 'h3';

    // The verdict in the AI Findings words (AiFinding): Cleared when every check passed, Revision when one did not
    // (a new photo or a corrected form is needed), Unchecked while it runs or when the check could not finish.
    $checks = [
        'matches_document' => 'Document layout match',
        'readable' => 'Text legibility and clarity',
        'fields_complete' => 'All mandatory fields populated',
        'has_signature_or_stamp' => 'Official stamp or signature',
        'fields_match_expected' => 'Field data matches record',
    ];
    $passes = collect($checks)->map(fn ($l, $key) => (bool) ($data[$key] ?? ($key === 'fields_match_expected')));
    $key = match (true) {
        $ws->isProcessing() || ! empty($data['error_code']) || ! $hasContent => 'unchecked',
        $passes->every(fn ($p) => $p) => 'cleared',
        default => 'revision',
    };
    $icon = match (true) {
        $ws->isProcessing() => 'bx-loader-alt bx-spin',
        ! empty($data['error_code']) => 'bx-error-circle',
        default => null,
    };
@endphp

<div class="space-y-4" data-ai-findings>
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 text-[#2A57B4]" aria-hidden="true"><i class="bx bx-bot text-lg"></i></span>
            <{{ $headingTag }} class="text-sm font-semibold text-slate-900">AI Findings</{{ $headingTag }}>
        </div>
        <span class="{{ \App\Support\StatusStyle::BADGE }} {{ \App\Support\AiFinding::pill($key) }}">
            @if ($icon)<i class="bx {{ $icon }} text-sm" aria-hidden="true"></i>@endif
            {{ \App\Support\AiFinding::word($key) }}
        </span>
    </div>

    <p class="text-xs text-slate-500">{{ \App\Support\AiFinding::NOTE }}</p>

    @if ($ws->analysis_summary)
        <blockquote class="rounded-lg border-l-4 border-slate-300 bg-slate-50/80 p-3 text-sm italic text-slate-600">
            "{{ $ws->analysis_summary }}"
        </blockquote>
    @endif

    @if ($hasContent)
        <ul class="grid gap-2 sm:grid-cols-2">
            @foreach ($checks as $k => $label)
                @php $pass = $passes[$k]; @endphp
                <li class="flex items-center gap-2 rounded-lg border border-slate-100 p-2.5 text-sm">
                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $pass ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' }}" aria-hidden="true">
                        <i class="bx {{ $pass ? 'bx-check' : 'bx-x' }}"></i>
                    </span>
                    <span class="{{ $pass ? 'font-medium text-slate-700' : 'text-slate-500' }}">{{ $label }}<span class="sr-only">: {{ $pass ? 'passed' : 'failed' }}</span></span>
                </li>
            @endforeach
        </ul>

        @if (! empty($data['missing_fields']))
            <div class="rounded-lg border border-amber-200/60 bg-amber-50/50 p-3">
                <p class="text-sm font-semibold text-amber-800">Incomplete fields detected</p>
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($data['missing_fields'] as $field)
                        <span class="rounded-md border border-amber-300/60 bg-white px-2 py-0.5 text-xs font-medium text-amber-800">{{ $field }}</span>
                    @endforeach
                </div>
            </div>
        @endif

        @if (! empty($data['field_matches']))
            <div class="space-y-2">
                <p class="text-sm font-semibold text-slate-700">Detailed data validation</p>

                {{-- Phones: one block per field --}}
                <ul class="space-y-2 md:hidden">
                    @foreach ($data['field_matches'] as $fm)
                        <li class="rounded-lg border border-slate-200/80 p-3 text-sm">
                            <div class="flex items-center justify-between gap-2">
                                <p class="font-medium text-slate-800">{{ $fm['label'] ?? '—' }}</p>
                                <i class="bx {{ ($fm['matches'] ?? false) ? 'bx-check-circle text-emerald-600' : 'bx-x-circle text-rose-600' }} text-lg" aria-label="{{ ($fm['matches'] ?? false) ? 'Matches' : 'Does not match' }}"></i>
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
                            @foreach ($data['field_matches'] as $fm)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="px-3 py-2 font-medium text-slate-800">{{ $fm['label'] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $fm['expected_value'] ?? '—' }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ $fm['found_value'] ?? '—' }}</td>
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

        @if (! empty($data['authenticity_notes']))
            <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-900">
                <i class="bx bx-info-circle mt-0.5 shrink-0" aria-hidden="true"></i>
                <span>{{ $data['authenticity_notes'] }}</span>
            </div>
        @endif
    @endif

    @if ($ws->ocr_text)
        <details class="group rounded-lg border border-slate-200/80 bg-slate-50/50 p-3">
            <summary class="flex cursor-pointer items-center justify-between text-sm font-semibold text-slate-600 group-open:text-slate-900">
                <span>View extracted text</span>
                <i class="bx bx-chevron-down text-base transition-transform group-open:rotate-180" aria-hidden="true"></i>
            </summary>
            <p class="mt-2 whitespace-pre-wrap break-words border-t border-slate-200/60 pt-2 font-mono text-[11px] leading-relaxed text-slate-600">{{ $ws->ocr_text }}</p>
        </details>
    @endif
</div>
