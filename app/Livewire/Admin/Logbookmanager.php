<?php

namespace App\Livewire\Admin;

use App\Models\LogbookEntry;
use App\Models\LogbookUpload;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class LogbookManager extends Component
{
    use WithFileUploads;

    // ---- Upload / parse state ----
    public $file = null;
    public bool $isParsing = false;

    // Popup shown when a non-excel file is selected.
    public bool $showInvalidFileModal = false;
    public string $invalidFileMessage = '';

    // Confirmation modal: preview of everything that will be stored.
    public bool $showConfirmModal = false;
    public string $previewToken = '';
    public array $previewHeaders = [];
    public int $previewRowCount = 0;
    public int $previewColumnCount = 0;
    public string $previewFilename = '';
    public int $previewPage = 1;
    public int $previewPerPage = 25;

    public string $filterProgram = '';
    public string $filterPurpose = '';

    // ---- Stored logbook list state ----
    public string $search = '';

    public string $dateFrom = '';
    public string $dateTo = '';
    public ?int $selectedUploadId = null;
    public int $currentPage = 1;
    public int $perPage = 30;

    protected $listeners = ['logbookUploadConfirmed' => '$refresh'];

    protected function rulesForFile(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'], // 20MB
        ];
    }

    public function updatedFile(): void
    {
        if ($this->file === null) {
            return;
        }

        $this->resetErrorBag();
        $this->showInvalidFileModal = false;

        // Reject anything that isn't an excel file up front, with a clear popup.
        $extension = strtolower($this->file?->getClientOriginalExtension() ?? '');

        if (! in_array($extension, ['xlsx', 'xls'], true)) {
            $this->file = null;
            $this->invalidFileMessage = 'Only Excel files (.xlsx or .xls) are accepted. Please choose a valid Excel file.';
            $this->showInvalidFileModal = true;
            return;
        }

        try {
            $this->validate($this->rulesForFile());
        } catch (Throwable $e) {
            $this->file = null;
            $this->invalidFileMessage = 'That file could not be validated. Please choose a valid Excel file under 20MB.';
            $this->showInvalidFileModal = true;
            return;
        }

        $this->parseUploadedFile();
    }

    public function dismissInvalidFileModal(): void
    {
        $this->showInvalidFileModal = false;
        $this->invalidFileMessage = '';
    }

    /**
     * Read the uploaded excel file, auto-detect the header row (looks for the
     * "Date" / "Time" columns used by the VPSD logbook), and stash the parsed
     * rows in cache so the admin can review everything before it's saved.
     */
    protected function parseUploadedFile(): void
    {
        $this->isParsing = true;

        try {
            $spreadsheet = IOFactory::load($this->file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            $grid = $sheet->toArray(null, true, true, false); // 0-indexed rows/cols, formatted values

            [$headerRowIndex, $headers] = $this->detectHeaderRow($grid);

            if ($headerRowIndex === null) {
                $this->file = null;
                $this->isParsing = false;
                $this->invalidFileMessage = 'This file doesn\'t look like a VPSD logbook export — no "Date" / "Time" header row was found.';
                $this->showInvalidFileModal = true;
                return;
            }

            $rows = [];
            for ($i = $headerRowIndex + 1; $i < count($grid); $i++) {
                $line = $grid[$i];
                $isBlank = collect($line)->every(fn ($v) => trim((string) $v) === '');
                if ($isBlank) {
                    continue;
                }

                $row = [];
                foreach ($headers as $colIndex => $label) {
                    $row[$label] = trim((string) ($line[$colIndex] ?? ''));
                }
                $rows[] = $row;
            }

            $token = (string) Str::uuid();
            Cache::put("logbook-preview:{$token}", [
                'filename' => $this->file->getClientOriginalName(),
                'headers' => $headers,
                'rows' => $rows,
            ], now()->addMinutes(30));

            $this->previewToken = $token;
            $this->previewHeaders = $headers;
            $this->previewFilename = $this->file->getClientOriginalName();
            $this->previewRowCount = count($rows);
            $this->previewColumnCount = count($headers);
            $this->previewPage = 1;
            $this->showConfirmModal = true;
        } catch (Throwable $e) {
            $this->invalidFileMessage = 'This file could not be read as an Excel file. Please check it and try again.';
            $this->showInvalidFileModal = true;
        }

        $this->file = null; // temp upload no longer needed, data lives in cache now
        $this->isParsing = false;
    }

    /**
     * Scan the first ~20 rows for the "Date" / "Time" header pair used by the
     * VPSD logbook template (title rows above it vary in count between files).
     */
    protected function detectHeaderRow(array $grid): array
    {
        $scanLimit = min(count($grid), 20);

        for ($i = 0; $i < $scanLimit; $i++) {
            $a = Str::lower(trim((string) ($grid[$i][0] ?? '')));
            $b = Str::lower(trim((string) ($grid[$i][1] ?? '')));

            if ($a === 'date' && $b === 'time') {
                $headers = [];
                foreach ($grid[$i] as $colIndex => $value) {
                    $label = trim((string) $value);
                    if ($label !== '') {
                        $headers[$colIndex] = $label;
                    }
                }
                return [$i, $headers];
            }
        }

        return [null, []];
    }

    public function getPreviewRowsProperty(): array
    {
        if ($this->previewToken === '') {
            return [];
        }

        $data = Cache::get("logbook-preview:{$this->previewToken}");
        if (! $data) {
            return [];
        }

        $offset = ($this->previewPage - 1) * $this->previewPerPage;

        return array_slice($data['rows'], $offset, $this->previewPerPage);
    }

    public function getPreviewTotalPagesProperty(): int
    {
        return max(1, (int) ceil($this->previewRowCount / $this->previewPerPage));
    }

    public function previewPreviousPage(): void
    {
        if ($this->previewPage > 1) {
            $this->previewPage--;
        }
    }

    public function previewNextPage(): void
    {
        if ($this->previewPage < $this->previewTotalPages) {
            $this->previewPage++;
        }
    }

    public function cancelUpload(): void
    {
        if ($this->previewToken !== '') {
            Cache::forget("logbook-preview:{$this->previewToken}");
        }

        $this->reset([
            'file', 'showConfirmModal', 'previewToken', 'previewHeaders',
            'previewRowCount', 'previewColumnCount', 'previewFilename', 'previewPage',
        ]);
    }

    /**
     * Admin has reviewed the preview and confirms — persist everything.
     */
    public function confirmUpload(): void
    {
        $data = Cache::get("logbook-preview:{$this->previewToken}");

        if (! $data || empty($data['rows'])) {
            $this->cancelUpload();
            return;
        }

        $fieldMap = $this->fieldMap($data['headers']);

        DB::transaction(function () use ($data, $fieldMap) {
            $upload = LogbookUpload::create([
                'uploaded_by' => Auth::id(),
                'original_filename' => $data['filename'],
                'headers' => array_values($data['headers']),
                'row_count' => count($data['rows']),
                'column_count' => count($data['headers']),
                'uploaded_at' => now(),
            ]);

            $rowNumber = 0;
            foreach (array_chunk($data['rows'], 200) as $chunk) {
                $records = [];
                foreach ($chunk as $row) {
                    $rowNumber++;
                    $records[] = [
                        'logbook_upload_id' => $upload->id,
                        'row_number' => $rowNumber,
                        'entry_date' => $row[$fieldMap['entry_date'] ?? ''] ?? null,
                        'entry_time' => $row[$fieldMap['entry_time'] ?? ''] ?? null,
                        'applicant_name' => $row[$fieldMap['applicant_name'] ?? ''] ?? null,
                        'program' => $row[$fieldMap['program'] ?? ''] ?? null,
                        'address' => $row[$fieldMap['address'] ?? ''] ?? null,
                        'issued_to' => $row[$fieldMap['issued_to'] ?? ''] ?? null,
                        'relation' => $row[$fieldMap['relation'] ?? ''] ?? null,
                        'purpose' => $row[$fieldMap['purpose'] ?? ''] ?? null,
                        'released_at' => $row[$fieldMap['released_at'] ?? ''] ?? null,
                        'raw_data' => json_encode($row),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                LogbookEntry::insert($records);
            }
        });

        Cache::forget("logbook-preview:{$this->previewToken}");

        $this->reset([
            'file', 'showConfirmModal', 'previewToken', 'previewHeaders',
            'previewRowCount', 'previewColumnCount', 'previewFilename', 'previewPage',
        ]);

        $this->currentPage = 1;
        session()->flash('logbook-success', 'Logbook uploaded and saved successfully.');

        // Charts live in a wire:ignore block so Livewire never touches their canvases;
        // tell the front end to pull fresh numbers now that new rows exist.
        $this->dispatch('logbook-charts-refresh');
    }

    /**
     * Map known VPSD logbook columns to whichever header labels this file used.
     */
    protected function fieldMap(array $headers): array
    {
        $map = [];

        foreach ($headers as $label) {
            $key = Str::lower(trim($label));

            $map += match (true) {
                $key === 'date' => ['entry_date' => $label],
                $key === 'time' => ['entry_time' => $label],
                Str::contains($key, 'applicant') => ['applicant_name' => $label],
                Str::contains($key, 'course') || Str::contains($key, 'program') => ['program' => $label],
                Str::contains($key, 'address') => ['address' => $label],
                $key === 'issued to' => ['issued_to' => $label],
                Str::contains($key, 'relation') => ['relation' => $label],
                Str::contains($key, 'purpose') => ['purpose' => $label],
                Str::contains($key, 'released') => ['released_at' => $label],
                default => [],
            };
        }

        return $map;
    }

    // ---- Stored logbook entries table ----

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }

    public function openUploadBatch(int $uploadId): void
    {
        $this->selectedUploadId = $uploadId;
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }

    public function closeUploadBatch(): void
    {
        $this->selectedUploadId = null;
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }

    public function deleteUpload(int $uploadId): void
    {
        LogbookUpload::whereKey($uploadId)->delete(); // cascades to entries

        if ($this->selectedUploadId === $uploadId) {
            $this->selectedUploadId = null;
        }

        $this->dispatch('logbook-charts-refresh');
    }

    protected function entriesQuery()
    {
        return LogbookEntry::query()
            ->with('upload')
            ->when($this->selectedUploadId, fn ($q) => $q->where('logbook_upload_id', $this->selectedUploadId))
            ->when($this->filterProgram !== '', fn ($q) => $q->where('program', $this->filterProgram))
            ->when($this->filterPurpose !== '', fn ($q) => $q->where('purpose', $this->filterPurpose))
            ->when($this->search !== '', function ($q) {
                $needle = '%' . $this->search . '%';
                $q->where(function ($q) use ($needle) {
                    $q->where('applicant_name', 'like', $needle)
                        ->orWhere('program', 'like', $needle)
                        ->orWhere('address', 'like', $needle)
                        ->orWhere('issued_to', 'like', $needle)
                        ->orWhere('purpose', 'like', $needle);
                });
            })
            ->orderByDesc('row_number');
    }

    //----///
    public function getProgramOptionsProperty(): array
    {
        return LogbookEntry::query()
            ->whereNotNull('program')->where('program', '!=', '')
            ->distinct()->orderBy('program')->pluck('program')->toArray();
    }

    public function getPurposeOptionsProperty(): array
    {
        return LogbookEntry::query()
            ->whereNotNull('purpose')->where('purpose', '!=', '')
            ->distinct()->orderBy('purpose')->pluck('purpose')->toArray();
    }

    public function updatedDateFrom(): void
    {
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }

    public function updatedDateTo(): void
    {
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }

    public function clearDateFilter(): void
    {
        $this->reset(['dateFrom', 'dateTo']);
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }

    // ---- Analytics: three charts (Purpose, Monthly, Program) shown one at a ----
    // ---- time in a carousel, always over the full lifetime of the logbook.  ----
    // ---- (Per-chart date/topN/year filters were removed — they weren't    ----
    // ---- working reliably, so charts now just reflect everything.)        ----

    /**
     * How many bars to show on the ranked charts (Purpose, Program) before
     * lumping the rest — no UI control for this anymore, just a sane cap.
     */
    protected int $topN = 10;

    /**
     * Pulls every entry's purpose/program/parsed-date once so all three
     * charts can be built from the same in-memory set. entry_date is free
     * text (source files mix date formats), so parsing happens here,
     * defensively, per row.
     */
    protected function parseEntryDate(?string $raw): ?Carbon
    {
        if (blank($raw)) {
            return null;
        }
        try {
            return Carbon::parse($raw);
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function withinDateFilter(?Carbon $date): bool
    {
        if ($this->dateFrom === '' && $this->dateTo === '') {
            return true;
        }
        if (! $date) {
            return false; // unparsable dates are excluded once a filter is active
        }
        if ($this->dateFrom !== '' && $date->lt(Carbon::parse($this->dateFrom)->startOfDay())) {
            return false;
        }
        if ($this->dateTo !== '' && $date->gt(Carbon::parse($this->dateTo)->endOfDay())) {
            return false;
        }
        return true;
    }

    protected function baseEntries(): Collection
    {
        return $this->entriesQuery()
            ->get(['purpose', 'program', 'entry_date'])
            ->map(fn ($entry) => [
                'purpose' => $entry->purpose,
                'program' => $entry->program,
                'date' => $this->parseEntryDate($entry->entry_date),
            ])
            ->filter(fn ($row) => $this->withinDateFilter($row['date']))
            ->values();
    }

    protected function rangeLabel(): string
    {
        $parts = [];

        if ($this->selectedUploadId) {
            $parts[] = 'This upload';
        }
        if ($this->filterProgram !== '') {
            $parts[] = 'Program: ' . $this->filterProgram;
        }
        if ($this->filterPurpose !== '') {
            $parts[] = 'Purpose: ' . $this->filterPurpose;
        }
        if ($this->search !== '') {
            $parts[] = 'Search: "' . $this->search . '"';
        }
        if ($this->dateFrom !== '' || $this->dateTo !== '') {
            $from = $this->dateFrom !== '' ? Carbon::parse($this->dateFrom)->format('M j, Y') : 'Start';
            $to = $this->dateTo !== '' ? Carbon::parse($this->dateTo)->format('M j, Y') : 'Today';
            $parts[] = "{$from} – {$to}";
        }

        return $parts ? implode(' · ', $parts) : 'Lifetime';
    }

    protected function purposeDistribution(Collection $entries): array
    {
        return $entries->pluck('purpose')->filter(fn ($p) => filled($p))->countBy()->sortDesc()->take($this->topN)->toArray();
    }

    protected function programDistribution(Collection $entries): array
    {
        return $entries->pluck('program')->filter(fn ($p) => filled($p))->countBy()->sortDesc()->take($this->topN)->toArray();
    }
    public function highlight(?string $text): string
    {
        $text = (string) $text;
        if (trim($this->search) === '' || trim($text) === '') {
            return e($text);
        }

        $escaped = e($text);
        $needle = preg_quote(e($this->search), '/');

        return preg_replace(
            '/(' . $needle . ')/i',
            '<mark class="rounded bg-amber-200 px-0.5 text-slate-900">$1</mark>',
            $escaped
        ) ?? $escaped;
    }

    protected function monthlyTrend(Collection $entries): array
    {
        $counts = [];
        foreach ($entries as $row) {
            if (! $row['date']) {
                continue;
            }
            $key = $row['date']->format('Y-m');
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        ksort($counts);

        $labeled = [];
        foreach ($counts as $ym => $total) {
            $labeled[Carbon::createFromFormat('Y-m', $ym)->format('M Y')] = $total;
        }

        return $labeled;
    }

    protected function chartPayload(): array
    {
        $base = $this->baseEntries();
        $label = $this->rangeLabel();

        return [
            'purpose' => $this->purposeDistribution($base),
            'purposeMeta' => ['rangeLabel' => $label, 'totalEntries' => $base->count()],
            'monthly' => $this->monthlyTrend($base),
            'monthlyMeta' => ['rangeLabel' => $label, 'totalEntries' => $base->count()],
            'program' => $this->programDistribution($base),
            'programMeta' => ['rangeLabel' => $label, 'totalEntries' => $base->count()],
        ];
    }

    public function updatedFilterProgram(): void
    {
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }

    public function updatedFilterPurpose(): void
    {
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }

    public function clearAllFilters(): void
    {
        $this->reset(['dateFrom', 'dateTo', 'filterProgram', 'filterPurpose']);
        $this->currentPage = 1;
        $this->dispatch('logbook-charts-refresh');
    }
    protected function filteredEntries(): Collection
    {
        return $this->entriesQuery()
            ->get()
            ->filter(fn ($entry) => $this->withinDateFilter($this->parseEntryDate($entry->entry_date)))
            ->values();
    }

    /**
     * Called from the front end (wire:ignore chart block) to refresh chart
     * data without Livewire re-rendering/destroying the canvases.
     */
    public function getChartData(): array
    {
        return $this->chartPayload();
    }

    public function render()
    {
        $uploads = LogbookUpload::withCount('entries')->latest('uploaded_at')->get();

        $filtered = $this->filteredEntries();
        $totalCount = $filtered->count();
        $totalPages = max(1, (int) ceil($totalCount / $this->perPage));
        $this->currentPage = min($this->currentPage, $totalPages);

        $entries = $filtered->slice(($this->currentPage - 1) * $this->perPage, $this->perPage)->values();

        $selectedUpload = $this->selectedUploadId
            ? $uploads->firstWhere('id', $this->selectedUploadId)
            : null;

        return view('livewire.admin.logbook-manager', array_merge([
            'uploads' => $uploads,
            'entries' => $entries,
            'totalCount' => $totalCount,
            'totalPages' => $totalPages,
            'selectedUpload' => $selectedUpload,
        ], $this->chartPayload()));
    }
}