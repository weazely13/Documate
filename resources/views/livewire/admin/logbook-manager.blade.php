@php
    $fieldClass = 'rounded-lg border-gray-300 py-2.5 text-base shadow-sm sm:py-2 sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';
    $smField = 'rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';
@endphp

<div class="space-y-5" x-data>

    <x-admin.alert :message="session('logbook-success')" type="success" class="!mb-0" />

    {{-- ============ TOP ROW: analytics (3/4) + uploaded files (1/4) ============ --}}
    <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">

        {{-- ===== Analytics carousel ===== --}}
        <div class="lg:col-span-3"
             x-data="{
                 activeChart: 0,
                 chartOrder: ['purpose', 'monthly', 'program'],
                 chartTitles: { purpose: 'Requests by purpose', monthly: 'Monthly trend', program: 'Requests by course / program' },
                 prevChart() { this.activeChart = (this.activeChart + this.chartOrder.length - 1) % this.chartOrder.length; $dispatch('chart-switched'); },
                 nextChart() { this.activeChart = (this.activeChart + 1) % this.chartOrder.length; $dispatch('chart-switched'); },
             }">
            <x-admin.section title="Logbook analytics" description="Always shows the current filters and selected upload.">
                <x-slot:actions>
                    <div class="flex items-center gap-1">
                        <template x-for="(key, i) in chartOrder" :key="key">
                            <span class="h-1.5 w-1.5 rounded-full transition" :class="i === activeChart ? 'bg-[#2A57B4]' : 'bg-gray-200'"></span>
                        </template>
                    </div>
                    <div class="inline-flex overflow-hidden rounded-lg border border-gray-200 bg-white">
                        <button type="button" @click="prevChart()" aria-label="Previous chart"
                                class="border-r border-gray-200 px-2.5 py-1.5 text-gray-500 transition hover:bg-gray-50 hover:text-[#2A57B4]">
                            <i class='bx bx-chevron-left'></i>
                        </button>
                        <button type="button" @click="nextChart()" aria-label="Next chart"
                                class="px-2.5 py-1.5 text-gray-500 transition hover:bg-gray-50 hover:text-[#2A57B4]">
                            <i class='bx bx-chevron-right'></i>
                        </button>
                    </div>
                </x-slot:actions>

                {{-- wire:ignore keeps Chart.js canvases alive; refreshed via $wire.getChartData() --}}
                <div wire:ignore
                     x-data="logbookCharts({{ \Illuminate\Support\Js::from(['purpose' => $purpose, 'purposeMeta' => $purposeMeta, 'monthly' => $monthly, 'monthlyMeta' => $monthlyMeta, 'program' => $program, 'programMeta' => $programMeta]) }})"
                     x-init="init()"
                     x-on:chart-switched.window="handleChartSwitch()">

                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-sm font-semibold text-[#2A57B4]" x-text="chartTitles[chartOrder[activeChart]]"></p>
                            <p class="text-xs text-gray-500">
                                <span x-text="meta(chartOrder[activeChart]).rangeLabel"></span>
                                · <span x-text="meta(chartOrder[activeChart]).totalEntries"></span> entries
                            </p>
                        </div>
                        <button type="button" @click="download(chartOrder[activeChart] + 'Chart', 'logbook-' + chartOrder[activeChart])"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition hover:bg-gray-50 hover:text-[#2A57B4]">
                            <i class='bx bx-download'></i> Export PNG
                        </button>
                    </div>

                    <div class="relative h-64 rounded-lg border border-gray-100 bg-gray-50/60 p-3 sm:h-80">
                        <div class="absolute inset-3 transition-opacity duration-150"
                             :class="chartOrder[activeChart] === 'purpose' ? 'z-10 opacity-100' : 'pointer-events-none z-0 opacity-0'">
                            <canvas x-ref="purposeChart"></canvas>
                        </div>
                        <div class="absolute inset-3 transition-opacity duration-150"
                             :class="chartOrder[activeChart] === 'monthly' ? 'z-10 opacity-100' : 'pointer-events-none z-0 opacity-0'">
                            <canvas x-ref="monthlyChart"></canvas>
                        </div>
                        <div class="absolute inset-3 transition-opacity duration-150"
                             :class="chartOrder[activeChart] === 'program' ? 'z-10 opacity-100' : 'pointer-events-none z-0 opacity-0'">
                            <canvas x-ref="programChart"></canvas>
                        </div>
                    </div>
                </div>
            </x-admin.section>
        </div>

        {{-- ===== Uploaded logbooks ===== --}}
        <div class="lg:col-span-1">
            <x-admin.section title="Uploaded logbooks" :description="$uploads->count() . ' ' . \Illuminate\Support\Str::plural('file', $uploads->count())" :padded="false" class="h-full">
                <x-slot:actions>
                    <label class="relative inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700"
                           wire:loading.class="pointer-events-none opacity-70" wire:target="file" title="Upload logbook (Excel)">
                        <i class='bx bx-upload'></i>
                        <span wire:loading.remove wire:target="file">Upload</span>
                        <span wire:loading wire:target="file">Reading…</span>
                        <input type="file" class="hidden" wire:model="file" accept=".xlsx,.xls">
                    </label>
                </x-slot:actions>

                <div class="max-h-72 divide-y divide-gray-100 overflow-y-auto lg:max-h-[26rem]">
                    @forelse ($uploads as $upload)
                        <div wire:key="upload-card-{{ $upload->id }}"
                             class="group flex items-start gap-2 px-4 py-3 transition {{ $selectedUploadId === $upload->id ? 'bg-[#2A57B4]/5' : 'hover:bg-gray-50' }}">
                            <button type="button" wire:click="openUploadBatch({{ $upload->id }})" class="flex min-w-0 flex-1 items-start gap-3 text-left">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                                    <i class='bx bxs-file-blank text-lg'></i>
                                </span>
                                <span class="min-w-0">
                                    @if ($selectedUploadId === $upload->id)
                                        <span class="mb-1 inline-block rounded-full bg-[#2A57B4] px-1.5 py-0.5 text-[10px] font-medium text-white">Viewing</span>
                                    @endif
                                    <span class="block truncate text-sm font-medium text-gray-900 group-hover:text-[#2A57B4]" title="{{ $upload->original_filename }}">{{ $upload->original_filename }}</span>
                                    <span class="block text-xs text-gray-500">{{ number_format($upload->entries_count) }} rows · {{ $upload->column_count }} cols</span>
                                    <span class="block text-xs text-gray-400">{{ $upload->uploaded_at->diffForHumans() }}</span>
                                </span>
                            </button>
                            <button type="button" wire:click="deleteUpload({{ $upload->id }})"
                                    wire:confirm="Delete this logbook and all {{ $upload->entries_count }} of its rows? This can't be undone."
                                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                    title="Delete upload" aria-label="Delete upload">
                                <i class='bx bx-trash'></i>
                            </button>
                        </div>
                    @empty
                        <div class="px-4 py-12 text-center">
                            <p class="text-sm text-gray-400">No logbooks uploaded yet.</p>
                            <p class="mt-1 text-xs text-gray-400">Use Upload to add an Excel export.</p>
                        </div>
                    @endforelse
                </div>
            </x-admin.section>
        </div>
    </div>

    {{-- ============ ENTRIES ============ --}}
    <div>
        @if ($selectedUpload)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-[#2A57B4]/20 bg-[#2A57B4]/5 px-4 py-2.5 text-sm text-[#2A57B4]">
                <span>Viewing entries from <strong>{{ $selectedUpload->original_filename }}</strong></span>
                <button type="button" wire:click="closeUploadBatch" class="text-xs font-semibold underline hover:text-blue-900">Show all uploads</button>
            </div>
        @endif

        {{-- Toolbar --}}
        <div class="col-span-2 grid grid-cols-[1fr_auto_1fr] items-center gap-1.5 sm:col-span-1 sm:flex">
            <div class="relative col-span-2 sm:min-w-[240px] sm:flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
                </svg>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search applicant, program, purpose…"
                       class="w-full pl-9 pr-3 {{ $fieldClass }}">
            </div>

            <select wire:model.live="filterProgram" aria-label="Filter by program" class="w-full sm:w-auto sm:max-w-[200px] {{ $fieldClass }}">
                <option value="">All programs</option>
                @foreach ($this->programOptions as $program)
                    <option value="{{ $program }}">{{ $program }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterPurpose" aria-label="Filter by purpose" class="w-full sm:w-auto sm:max-w-[200px] {{ $fieldClass }}">
                <option value="">All purposes</option>
                @foreach ($this->purposeOptions as $purpose)
                    <option value="{{ $purpose }}">{{ $purpose }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-1.5">
                <input type="date" wire:model.live="dateFrom" aria-label="From date" class="w-full sm:w-auto {{ $fieldClass }}">
                <span class="text-xs text-gray-400">to</span>
                <input type="date" wire:model.live="dateTo" aria-label="To date" class="w-full sm:w-auto {{ $fieldClass }}">
            </div>

            @if ($dateFrom || $dateTo || $filterProgram || $filterPurpose)
                <button type="button" wire:click="clearAllFilters"
                        class="col-span-2 py-2 text-center text-xs font-medium text-gray-500 hover:text-red-600 sm:col-span-1 sm:px-2 sm:py-0">Clear filters</button>
            @endif

            <span class="col-span-2 text-xs text-gray-500 sm:ml-auto">{{ number_format($totalCount) }} {{ \Illuminate\Support\Str::plural('entry', $totalCount) }}</span>
        </div>

        {{-- Table --}}
        <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Date</th>
                            <th class="px-5 py-3 text-left">Time</th>
                            <th class="min-w-[14rem] border-l-2 border-[#2A57B4] bg-[#2A57B4]/5 px-5 py-3 text-left text-[#2A57B4]">Purpose</th>
                            <th class="px-5 py-3 text-left">Applicant</th>
                            <th class="px-5 py-3 text-left">Course / Program</th>
                            <th class="px-5 py-3 text-left">Address</th>
                            <th class="px-5 py-3 text-left">Issued to</th>
                            <th class="px-5 py-3 text-left">Relation</th>
                            <th class="px-5 py-3 text-left">Released</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($entries as $entry)
                            <tr wire:key="logbook-entry-{{ $entry->id }}" class="align-top text-gray-600 transition-colors hover:bg-gray-50/70">
                                <td class="whitespace-nowrap px-5 py-3.5 text-xs text-gray-500">{{ $entry->entry_date }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-xs text-gray-500">{{ $entry->entry_time }}</td>
                                <td class="min-w-[14rem] border-l-2 border-[#2A57B4] bg-[#2A57B4]/[0.03] px-5 py-3.5">
                                    @if (filled($entry->purpose))
                                        <span class="font-medium text-[#2A57B4]">{!! $this->highlight($entry->purpose) !!}</span>
                                    @else
                                        <span class="text-xs italic text-gray-400">No purpose recorded</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 font-medium text-gray-900">{!! $this->highlight($entry->applicant_name) !!}</td>
                                <td class="px-5 py-3.5">{!! $this->highlight($entry->program) !!}</td>
                                <td class="px-5 py-3.5 text-xs text-gray-500">{!! $this->highlight($entry->address) !!}</td>
                                <td class="px-5 py-3.5">{!! $this->highlight($entry->issued_to) !!}</td>
                                <td class="px-5 py-3.5 text-xs text-gray-500">{{ $entry->relation }}</td>
                                <td class="whitespace-nowrap px-5 py-3.5 text-xs text-gray-500">{{ $entry->released_at }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-5 py-12 text-center text-sm text-gray-400">No logbook entries matched.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        {{-- Mobile: entry cards --}}
        <div class="space-y-3 md:hidden">
            @forelse ($entries as $entry)
                <div wire:key="logbook-card-{{ $entry->id }}" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="border-l-2 border-[#2A57B4] pl-3">
                        @if (filled($entry->purpose))
                            <p class="text-sm font-semibold text-[#2A57B4]">{!! $this->highlight($entry->purpose) !!}</p>
                        @else
                            <p class="text-xs italic text-gray-400">No purpose recorded</p>
                        @endif
                        <p class="mt-0.5 text-xs text-gray-400">{{ $entry->entry_date }} {{ $entry->entry_time }}</p>
                    </div>

                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-400">Applicant</dt>
                            <dd class="text-right font-medium text-gray-900">{!! $this->highlight($entry->applicant_name) ?: '—' !!}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-400">Program</dt>
                            <dd class="text-right text-gray-700">{!! $this->highlight($entry->program) ?: '—' !!}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-400">Address</dt>
                            <dd class="text-right text-xs text-gray-600">{!! $this->highlight($entry->address) ?: '—' !!}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-400">Issued to</dt>
                            <dd class="text-right text-gray-700">{!! $this->highlight($entry->issued_to) ?: '—' !!}
                                @if ($entry->relation)<span class="text-xs text-gray-400">({{ $entry->relation }})</span>@endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="shrink-0 text-gray-400">Released</dt>
                            <dd class="text-right text-xs text-gray-600">{{ $entry->released_at ?: '—' }}</dd>
                        </div>
                    </dl>
                </div>
            @empty
                <p class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-400">No logbook entries matched.</p>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($totalCount > 0)
            <div class="mb-12 mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-xs text-gray-500">
                    Showing {{ (($currentPage - 1) * $perPage) + 1 }}–{{ min($currentPage * $perPage, $totalCount) }} of {{ number_format($totalCount) }}
                </p>
                <div class="inline-flex overflow-hidden rounded-lg border border-gray-200 bg-white text-sm shadow-sm">
                    <button type="button" wire:click="$set('currentPage', {{ max(1, $currentPage - 1) }})" @disabled($currentPage === 1) aria-label="Previous page"
                            class="px-3.5 py-2 transition {{ $currentPage === 1 ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:bg-gray-50' }}">
                        <i class='bx bx-chevron-left'></i>
                    </button>
                    <div class="flex items-center border-x border-gray-200 px-4 text-gray-600">{{ $currentPage }} / {{ $totalPages }}</div>
                    <button type="button" wire:click="$set('currentPage', {{ min($totalPages, $currentPage + 1) }})" @disabled($currentPage === $totalPages) aria-label="Next page"
                            class="px-3.5 py-2 transition {{ $currentPage === $totalPages ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:bg-gray-50' }}">
                        <i class='bx bx-chevron-right'></i>
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- ============ CONFIRM UPLOAD MODAL ============ --}}
    @if ($showConfirmModal)
        <div class="fixed inset-0 z-[9999] flex items-end justify-center bg-black/60 backdrop-blur-sm sm:items-center sm:p-4"
             wire:key="confirm-upload-modal" role="dialog" aria-modal="true" aria-label="Confirm logbook upload">
            <div class="flex max-h-[92dvh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl sm:rounded-2xl">

                <div class="flex items-start justify-between gap-3 border-b border-gray-100 px-5 py-4 sm:px-6">
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900">Confirm logbook upload</h3>
                        <p class="truncate text-xs text-gray-500">{{ $previewFilename }} · {{ number_format($previewRowCount) }} rows · {{ $previewColumnCount }} columns</p>
                    </div>
                    <button type="button" wire:click="cancelUpload" aria-label="Close"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-gray-100 hover:text-gray-700">
                        <i class='bx bx-x text-xl'></i>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-auto p-5 sm:p-6">
                    <p class="mb-3 text-xs text-gray-500">Review the entries below. This is a preview and nothing is stored until you confirm.</p>

                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs">
                                <thead class="border-b border-gray-100 bg-gray-50 font-medium text-gray-500">
                                    <tr>
                                        @foreach ($previewHeaders as $header)
                                            <th class="whitespace-nowrap px-3 py-2.5 text-left">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse ($this->previewRows as $i => $row)
                                        <tr wire:key="preview-row-{{ $i }}" class="text-gray-700">
                                            @foreach ($previewHeaders as $header)
                                                <td class="whitespace-nowrap px-3 py-2">{{ $row[$header] ?? '' }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr><td colspan="{{ max(1, count($previewHeaders)) }}" class="px-3 py-8 text-center text-gray-400">No rows found in this file.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($this->previewTotalPages > 1)
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-gray-500">Page {{ $previewPage }} of {{ $this->previewTotalPages }}</p>
                            <div class="inline-flex overflow-hidden rounded-lg border border-gray-200 bg-white text-xs shadow-sm">
                                <button type="button" wire:click="previewPreviousPage" @disabled($previewPage === 1) aria-label="Previous"
                                        class="px-3 py-1.5 {{ $previewPage === 1 ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:bg-gray-50' }}">
                                    <i class='bx bx-chevron-left'></i>
                                </button>
                                <button type="button" wire:click="previewNextPage" @disabled($previewPage === $this->previewTotalPages) aria-label="Next"
                                        class="border-l border-gray-200 px-3 py-1.5 {{ $previewPage === $this->previewTotalPages ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:bg-gray-50' }}">
                                    <i class='bx bx-chevron-right'></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-gray-100 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                    <button type="button" wire:click="cancelUpload"
                            class="rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-50 sm:py-2">Cancel</button>
                    <button type="button" wire:click="confirmUpload" wire:loading.attr="disabled" wire:target="confirmUpload"
                            class="rounded-lg bg-[#2A57B4] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:opacity-60 sm:py-2">
                        <span wire:loading.remove wire:target="confirmUpload">Confirm &amp; save {{ number_format($previewRowCount) }} entries</span>
                        <span wire:loading wire:target="confirmUpload">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ INVALID FILE MODAL ============ --}}
    @if ($showInvalidFileModal)
        <div class="fixed inset-0 z-[10000] flex items-end justify-center bg-black/60 sm:items-center sm:px-4"
             wire:key="invalid-file-modal" role="dialog" aria-modal="true">
            <div class="w-full max-w-md rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-50 text-2xl text-red-500">!</div>
                    <div class="min-w-0">
                        <h3 class="text-xl font-semibold text-gray-900">Invalid file</h3>
                        <p class="mt-2 text-sm leading-6 text-gray-500">{{ $invalidFileMessage }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="button" wire:click="dismissInvalidFileModal"
                            class="w-full rounded-lg bg-[#2A57B4] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 sm:w-auto sm:py-2">OK</button>
                </div>
            </div>
        </div>
    @endif
</div>