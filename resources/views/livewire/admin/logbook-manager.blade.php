<div class="space-y-4" x-data>

    {{-- Flash success --}}
    @if (session('logbook-success'))
        <div class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            <i class='bx bx-check-circle text-lg'></i> {{ session('logbook-success') }}
        </div>
    @endif

    {{-- ============ TOP ROW: analytics (3/4) + uploaded files list (1/4) ============ --}}
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">

        {{-- ================= LEFT (3/4): analytics carousel ================= --}}
        <div class="lg:col-span-3">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
                 x-data="{
                     activeChart: 0,
                     chartOrder: ['purpose', 'monthly', 'program'],
                     chartTitles: { purpose: 'Requests by Purpose', monthly: 'Monthly Trend', program: 'Requests by Course / Program' },
                     prevChart() { this.activeChart = (this.activeChart + this.chartOrder.length - 1) % this.chartOrder.length; $dispatch('chart-switched'); },
                     nextChart() { this.activeChart = (this.activeChart + 1) % this.chartOrder.length; $dispatch('chart-switched'); },
                 }">

                {{-- Carousel header: title of the active chart on the left; dots + prev/next
                     chart tabs on the right. --}}
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Current Status of the Logbook</h3>
                        <p class="text-xs font-semibold text-[#2A57B4]" x-text="chartTitles[chartOrder[activeChart]]"></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1">
                            <template x-for="(key, i) in chartOrder" :key="key">
                                <span class="h-1.5 w-1.5 rounded-full transition" :class="i === activeChart ? 'bg-[#2A57B4]' : 'bg-slate-200'"></span>
                            </template>
                        </div>
                        <div class="inline-flex overflow-hidden rounded-lg border border-slate-200">
                            <button type="button" @click="prevChart()"
                                    class="border-r border-slate-200 p-2 text-slate-500 transition hover:bg-slate-50 hover:text-[#2A57B4]" title="Previous chart">
                                <i class='bx bx-chevron-left'></i>
                            </button>
                            <button type="button" @click="nextChart()"
                                    class="p-2 text-slate-500 transition hover:bg-slate-50 hover:text-[#2A57B4]" title="Next chart">
                                <i class='bx bx-chevron-right'></i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Chart canvases — wire:ignore so Livewire never destroys them; refreshed via $wire.getChartData().
                     All three canvases stay mounted at all times (so Chart.js instances persist); only the active
                     one is visible, toggled purely by Alpine — switching charts never needs a server round trip. --}}
                <div wire:ignore
                     x-data="logbookCharts({{ \Illuminate\Support\Js::from(['purpose' => $purpose, 'purposeMeta' => $purposeMeta, 'monthly' => $monthly, 'monthlyMeta' => $monthlyMeta, 'program' => $program, 'programMeta' => $programMeta]) }})"
                     x-init="init()"
                     x-on:chart-switched.window="handleChartSwitch()">

                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-xs text-slate-400">
                            <span x-text="meta(chartOrder[activeChart]).rangeLabel"></span>
                            &middot; <span x-text="meta(chartOrder[activeChart]).totalEntries"></span> entries
                        </p>
                        <button type="button" @click="download(chartOrder[activeChart] + 'Chart', 'logbook-' + chartOrder[activeChart])"
                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-500 transition hover:bg-slate-50 hover:text-[#2A57B4]">
                            <i class='bx bx-download'></i> PNG
                        </button>
                    </div>

                    <div class="relative h-80 rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                        <div class="absolute inset-3 transition-opacity duration-150"
                            :class="chartOrder[activeChart] === 'purpose' ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'">
                            <canvas x-ref="purposeChart"></canvas>
                        </div>
                        <div class="absolute inset-3 transition-opacity duration-150"
                            :class="chartOrder[activeChart] === 'monthly' ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'">
                            <canvas x-ref="monthlyChart"></canvas>
                        </div>
                        <div class="absolute inset-3 transition-opacity duration-150"
                            :class="chartOrder[activeChart] === 'program' ? 'opacity-100 z-10' : 'opacity-0 z-0 pointer-events-none'">
                            <canvas x-ref="programChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ================= RIGHT (1/4): uploaded files list, no card wrapper ================= --}}
        {{-- This top row sits flush with the top of the page (same row as the "Transaction
             Records / Logbooks" tabs above), with the upload button anchoring it. --}}
        <div class="lg:col-span-1">
            <div class="mb-3 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-800">Uploaded Logbooks</h3>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500">{{ $uploads->count() }}</span>
                </div>

                <label class="relative inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-[#1f4494]"
                       wire:loading.class="pointer-events-none opacity-70" wire:target="file" title="Upload Logbook (Excel)">
                    <i class='bx bx-upload'></i>
                    <span wire:loading.remove wire:target="file" class="hidden sm:inline">Upload</span>
                    <span wire:loading wire:target="file" class="hidden sm:inline">Reading…</span>
                    <input type="file" class="hidden" wire:model="file" accept=".xlsx,.xls">
                </label>
            </div>

            <div class="max-h-[70vh] space-y-2 overflow-y-auto pr-1">
                @forelse($uploads as $upload)
                    <div wire:key="upload-card-{{ $upload->id }}"
                         class="group relative flex items-start gap-2.5 rounded-xl border p-3 transition {{ $selectedUploadId === $upload->id ? 'border-[#2A57B4] bg-[#2A57B4]/5 ring-1 ring-[#2A57B4]/20' : 'border-slate-200 bg-white hover:border-[#2A57B4]/40 hover:bg-slate-50' }}">
                        <button type="button" wire:click="openUploadBatch({{ $upload->id }})" class="flex min-w-0 flex-1 items-start gap-2.5 text-left">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-100 bg-slate-50">
                                <i class='bx bxs-file-blank text-lg text-emerald-500'></i>
                            </div>
                            <div class="min-w-0">
                                @if($selectedUploadId === $upload->id)
                                    <span class="mb-1 inline-flex items-center gap-1 rounded-full bg-[#2A57B4] px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                                        <i class='bx bx-show'></i> Viewing
                                    </span>
                                @endif
                                <h4 class="truncate text-xs font-bold text-slate-900 group-hover:text-[#2A57B4]" title="{{ $upload->original_filename }}">
                                    {{ $upload->original_filename }}
                                </h4>
                                <p class="mt-0.5 text-[11px] text-slate-500">{{ $upload->entries_count }} rows &middot; {{ $upload->column_count }} cols</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $upload->uploaded_at->diffForHumans() }}</p>
                            </div>
                        </button>
                        <button type="button" wire:click="deleteUpload({{ $upload->id }})"
                                wire:confirm="Delete this logbook and all {{ $upload->entries_count }} of its rows? This can't be undone."
                                class="shrink-0 rounded-lg p-1 text-slate-300 transition hover:bg-rose-50 hover:text-rose-500" title="Delete upload">
                            <i class='bx bx-trash'></i>
                        </button>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 px-4 py-10 text-center">
                        <i class='bx bx-folder-open text-2xl text-slate-300'></i>
                        <p class="mt-2 text-xs text-slate-500">No logbooks uploaded yet.</p>
                        <p class="mt-1 text-[11px] text-slate-400">Use the upload button above to add one.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ============ ENTRIES TABLE — full width, combined across every uploaded logbook ============ --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        {{-- Highlight banner: unmistakable indicator of which file's entries are being viewed --}}
        @if($selectedUpload)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[#2A57B4]/30 bg-[#2A57B4]/5 px-4 py-2.5">
                <p class="flex items-center gap-2 text-sm font-semibold text-[#2A57B4]">
                    <i class='bx bxs-file-blank text-base'></i>
                    Viewing entries from <span class="underline decoration-2 underline-offset-2">{{ $selectedUpload->original_filename }}</span>
                </p>
                <button type="button" wire:click="closeUploadBatch"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-[#2A57B4]/30 bg-white px-3 py-1.5 text-xs font-semibold text-[#2A57B4] transition hover:bg-[#2A57B4]/10">
                    <i class='bx bx-arrow-back'></i> All uploads
                </button>
            </div>
        @endif

        {{-- Toolbar --}}
        <div class="mb-5 space-y-3">

            {{-- Row 1: heading + search — search is the primary action, so it gets top billing and its own breathing room --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <h3 class="text-sm font-bold text-slate-800">
                    @if($selectedUpload)
                        Entries in this upload
                    @else
                        All Logbook Entries <span class="font-normal text-slate-400">(every upload combined)</span>
                    @endif
                </h3>

                <div class="relative w-full sm:w-80">
                    <i class='bx bx-search absolute left-3.5 top-1/2 -translate-y-1/2 text-lg text-slate-400'></i>
                    <input type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search applicant, program, purpose…"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-sm text-slate-700 outline-none transition focus:border-[#2A57B4] focus:bg-white focus:ring-4 focus:ring-[#2A57B4]/10">
                </div>
            </div>

            {{-- Row 2: filters — its own row so it wraps freely without fighting the heading/search for space --}}
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-xl border border-slate-200 bg-slate-50/60 px-3 py-2.5">
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-500">From</label>
                    <input type="date" wire:model.live="dateFrom"
                        class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/10">
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-500">To</label>
                    <input type="date" wire:model.live="dateTo"
                        class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/10">
                </div>

                <span class="hidden h-5 w-px bg-slate-200 sm:block"></span>

                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-500">Program</label>
                    <select wire:model.live="filterProgram"
                        class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/10">
                        <option value="">All Programs</option>
                        @foreach($this->programOptions as $program)
                            <option value="{{ $program }}">{{ $program }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-xs font-semibold text-slate-500">Purpose</label>
                    <select wire:model.live="filterPurpose"
                        class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700 outline-none focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/10">
                        <option value="">All Purposes</option>
                        @foreach($this->purposeOptions as $purpose)
                            <option value="{{ $purpose }}">{{ $purpose }}</option>
                        @endforeach
                    </select>
                </div>

                @if($dateFrom || $dateTo || $filterProgram || $filterPurpose)
                    <button type="button" wire:click="clearAllFilters"
                            class="ml-auto inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-rose-500">
                        <i class='bx bx-x'></i> Clear
                    </button>
                @endif
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse text-sm">
                    <thead class="bg-slate-50">
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Time</th>
                            <th class="px-4 py-3">Name of Applicant</th>
                            <th class="px-4 py-3">Course / Program</th>
                            <th class="px-4 py-3">Complete Address</th>
                            <th class="px-4 py-3">Issued To</th>
                            <th class="px-4 py-3">Relation</th>
                            <th class="px-4 py-3">Purpose</th>
                            <th class="px-4 py-3">Date &amp; Time Released</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($entries as $entry)
                            <tr wire:key="logbook-entry-{{ $entry->id }}" class="text-slate-700">
                                <td class="whitespace-nowrap px-4 py-3">{{ $entry->entry_date }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $entry->entry_time }}</td>
                                <td class="px-4 py-3 font-semibold text-slate-900">{!! $this->highlight($entry->applicant_name) !!}</td>
                                <td class="px-4 py-3">{!! $this->highlight($entry->program) !!}</td>
                                <td class="px-4 py-3">{!! $this->highlight($entry->address) !!}</td>
                                <td class="px-4 py-3">{!! $this->highlight($entry->issued_to) !!}</td>
                                <td class="px-4 py-3">{{ $entry->relation }}</td>
                                <td class="px-4 py-3">{!! $this->highlight($entry->purpose) !!}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $entry->released_at }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-14 text-center">
                                    <i class='bx bx-search-alt text-3xl text-slate-300'></i>
                                    <p class="mt-2 text-sm text-slate-500">No logbook entries matched.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($totalCount > 0)
            <div class="mt-6 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-xs text-slate-400">
                    Showing {{ (($currentPage - 1) * $perPage) + 1 }}–{{ min($currentPage * $perPage, $totalCount) }} of {{ $totalCount }}
                </p>
                <div class="inline-flex overflow-hidden rounded-xl border border-slate-200 text-sm">
                    <button type="button" wire:click="$set('currentPage', {{ max(1, $currentPage - 1) }})" @disabled($currentPage === 1)
                            class="px-4 py-2 transition {{ $currentPage === 1 ? 'cursor-not-allowed bg-slate-50 text-slate-300' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                        <i class='bx bx-chevron-left'></i>
                    </button>
                    <div class="flex items-center border-x border-slate-200 bg-white px-4 text-slate-600">
                        {{ $currentPage }} / {{ $totalPages }}
                    </div>
                    <button type="button" wire:click="$set('currentPage', {{ min($totalPages, $currentPage + 1) }})" @disabled($currentPage === $totalPages)
                            class="px-4 py-2 transition {{ $currentPage === $totalPages ? 'cursor-not-allowed bg-slate-50 text-slate-300' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                        <i class='bx bx-chevron-right'></i>
                    </button>
                </div>
            </div>
        @endif
    </div>
    {{-- ============ CONFIRM UPLOAD MODAL ============ --}}
    @if($showConfirmModal)
      
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/50 p-4" wire:key="confirm-upload-modal">
            <div class="flex max-h-[85vh] w-full max-w-4xl flex-col rounded-2xl bg-white shadow-xl">

                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Confirm Logbook Upload</h3>
                        <p class="text-xs text-slate-500">
                            {{ $previewFilename }} &middot; {{ $previewRowCount }} rows &middot; {{ $previewColumnCount }} columns
                        </p>
                    </div>
                    <button type="button" wire:click="cancelUpload" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <i class='bx bx-x text-xl'></i>
                    </button>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <p class="mb-3 text-xs text-slate-500">Review the entries below before saving. This is a preview — nothing is stored yet.</p>

                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-xs">
                                <thead class="bg-slate-50">
                                    <tr class="border-b border-slate-200 text-left font-semibold uppercase tracking-wide text-slate-500">
                                        @foreach($previewHeaders as $header)
                                            <th class="whitespace-nowrap px-3 py-2">{{ $header }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($this->previewRows as $i => $row)
                                        <tr wire:key="preview-row-{{ $i }}" class="text-slate-700">
                                            @foreach($previewHeaders as $header)
                                                <td class="whitespace-nowrap px-3 py-2">{{ $row[$header] ?? '' }}</td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ count($previewHeaders) }}" class="px-3 py-8 text-center text-slate-400">
                                                No rows found in this file.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($this->previewTotalPages > 1)
                        <div class="mt-3 flex items-center justify-between">
                            <p class="text-xs text-slate-400">Page {{ $previewPage }} of {{ $this->previewTotalPages }}</p>
                            <div class="inline-flex overflow-hidden rounded-lg border border-slate-200 text-xs">
                                <button type="button" wire:click="previewPreviousPage" @disabled($previewPage === 1)
                                        class="px-3 py-1.5 {{ $previewPage === 1 ? 'cursor-not-allowed bg-slate-50 text-slate-300' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                                    <i class='bx bx-chevron-left'></i>
                                </button>
                                <button type="button" wire:click="previewNextPage" @disabled($previewPage === $this->previewTotalPages)
                                        class="border-l border-slate-200 px-3 py-1.5 {{ $previewPage === $this->previewTotalPages ? 'cursor-not-allowed bg-slate-50 text-slate-300' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                                    <i class='bx bx-chevron-right'></i>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button type="button" wire:click="cancelUpload"
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Cancel
                    </button>
                    <button type="button" wire:click="confirmUpload" wire:loading.attr="disabled" wire:target="confirmUpload"
                            class="rounded-lg bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1f4494] disabled:opacity-60">
                        <span wire:loading.remove wire:target="confirmUpload">Confirm &amp; Save {{ $previewRowCount }} Entries</span>
                        <span wire:loading wire:target="confirmUpload">Saving…</span>
                    </button>
                </div>
            </div>
        </div>

        
    @endif

    {{-- ============ INVALID FILE MODAL ============ --}}
    @if($showInvalidFileModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" wire:key="invalid-file-modal">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-center gap-2 text-rose-600">
                    <i class='bx bx-error-circle text-xl'></i>
                    <h3 class="text-sm font-bold">Invalid File</h3>
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $invalidFileMessage }}</p>
                <div class="mt-5 flex justify-end">
                    <button type="button" wire:click="dismissInvalidFileModal"
                            class="rounded-lg bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white hover:bg-[#1f4494]">
                        OK
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>