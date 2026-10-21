@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $fieldClass = 'rounded-lg border-gray-300 py-2.5 text-base shadow-sm sm:py-2 sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';

    $statusClassFor = fn (string $s) => match ($s) {
        'Completed' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'For Appointment' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'Waiting Upload' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'Processing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'Missed', 'Rejected' => 'bg-rose-50 text-rose-700 ring-rose-200',
        'In Review' => 'bg-sky-50 text-sky-700 ring-sky-200',
        'Voided' => 'bg-gray-100 text-gray-600 ring-gray-200',
        default => 'bg-orange-50 text-orange-700 ring-orange-200',
    };
@endphp

<div>
    <x-admin.page-header
        title="Transactions"
        :description="$activeTab === 'transactions'
            ? 'Complete history of all document transactions.'
            : 'VPSD office logbooks uploaded from Excel exports.'"
        :crumbs="['Admin' => $homeUrl, 'Transactions' => null]" />

    {{-- Area tabs --}}
    <nav aria-label="Transaction sections" class="mb-6 flex gap-6 overflow-x-auto whitespace-nowrap border-b border-gray-200">
        @foreach ($mainTabs as $key => $text)
            <button type="button" wire:click="setActiveTab('{{ $key }}')"
                @if ($activeTab === $key) aria-current="page" @endif
                class="-mb-px border-b-2 px-1 pb-3 text-sm font-medium transition
                    {{ $activeTab === $key
                        ? 'border-[#2A57B4] text-[#2A57B4]'
                        : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                {{ $text }}
            </button>
        @endforeach
    </nav>

    @if ($activeTab === 'logbooks')

        <livewire:admin.logbook-manager />

    @else

        {{-- Toolbar --}}
        <div class="mb-3 grid grid-cols-2 gap-2 rounded-xl border border-gray-200 bg-white p-3 shadow-sm sm:flex sm:flex-wrap sm:items-center">
            <div class="relative col-span-2 sm:min-w-[240px] sm:flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
                </svg>
                <input type="search" wire:model.live.debounce.300ms="search"
                    placeholder="Search name, ID, document type, AI review notes…"
                    class="w-full pl-9 pr-3 {{ $fieldClass }}">
            </div>

            <select wire:model.live="sourceFilter" aria-label="Filter by source" class="w-full sm:w-auto {{ $fieldClass }}">
                <option value="All">All sources</option>
                <option value="Student-filed">Student-filed</option>
                <option value="Issued by office">Issued by office</option>
            </select>

            <select wire:model.live="sortBy" aria-label="Sort records" class="w-full sm:w-auto {{ $fieldClass }}">
                @foreach ($sortOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>

            <div class="col-span-2 inline-flex rounded-lg border border-gray-200 bg-white p-1 shadow-sm sm:col-span-1">
                @foreach (['table' => 'Table', 'folder' => 'Folders'] as $mode => $text)
                    <button type="button" wire:click="setViewMode('{{ $mode }}')"
                        class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition sm:flex-none
                            {{ $viewMode === $mode ? 'bg-[#2A57B4] text-white' : 'text-gray-500 hover:text-gray-800' }}">
                        {{ $text }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Status filter chips + summary --}}
        <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="-mx-1 flex gap-1.5 overflow-x-auto px-1 pb-1">
                @foreach ($statusTabs as $tab)
                    <button type="button" wire:click="setStatusFilter('{{ $tab }}')"
                        class="shrink-0 rounded-full border px-3.5 py-1.5 text-xs font-medium transition
                            {{ $statusFilter === $tab
                                ? 'border-[#2A57B4]/20 bg-[#2A57B4]/10 text-[#2A57B4]'
                                : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        {{ $tab }}
                    </button>
                @endforeach
            </div>
            <p class="text-xs leading-relaxed text-gray-500 sm:shrink-0 sm:text-right">
                {{ $pendingCount }} pending · {{ $forAppointmentCount }} for appointment · {{ $inReviewCount }} in review · {{ $completedCount }} completed
            </p>
        </div>

        {{-- ============ TABLE VIEW ============ --}}
        @if ($viewMode === 'table')
            <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-500">
                            <tr>
                                <th class="px-5 py-3 text-left">Student</th>
                                <th class="px-5 py-3 text-left">Student ID</th>
                                <th class="px-5 py-3 text-left">Forms</th>
                                <th class="px-5 py-3 text-left">Latest date</th>
                                <th class="px-5 py-3 text-left">Status</th>
                            </tr>
                        </thead>

                        @forelse ($students as $s)
                            <tbody wire:key="student-{{ $s['key'] }}" x-data="{ open: false }" class="border-t border-gray-100 first:border-t-0">
                                <tr @click="open = !open" class="group cursor-pointer align-top transition-colors hover:bg-gray-50/70">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <svg class="h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/>
                                            </svg>
                                            <div class="min-w-0">
                                                <div class="max-w-[220px] truncate font-medium text-gray-900 group-hover:text-[#2A57B4]" title="{{ $s['student_name'] }}">{{ $s['student_name'] }}</div>
                                                @if ($s['search_snippet'])
                                                    <p class="mt-1 max-w-[300px] text-xs italic leading-snug text-gray-400">
                                                        <span class="font-semibold not-italic text-gray-500">{{ $s['search_snippet_label'] }}:</span>
                                                        {!! $s['search_snippet'] !!}
                                                    </p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $s['student_number'] }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-600">{{ $s['total'] }} {{ Str::plural('form', $s['total']) }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-500">{{ $s['latest_label'] }}</td>
                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($s['status_counts'] as $status => $count)
                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $statusClassFor($status) }}">
                                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                    {{ $count > 1 ? $count . ' ' : '' }}{{ $status }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>

                                {{-- Expanded: this student's forms --}}
                                <tr x-show="open" x-cloak>
                                    <td colspan="5" class="border-t border-gray-100 bg-gray-50/60 px-5 py-3">
                                        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                                            <table class="w-full text-sm">
                                                <thead class="border-b border-gray-100 bg-gray-50 text-xs font-medium text-gray-500">
                                                    <tr>
                                                        <th class="px-4 py-2.5 text-left">Transaction ID</th>
                                                        <th class="px-4 py-2.5 text-left">Document</th>
                                                        <th class="px-4 py-2.5 text-left">Date</th>
                                                        <th class="px-4 py-2.5 text-left">Session</th>
                                                        <th class="px-4 py-2.5 text-left">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100">
                                                    @foreach ($s['forms'] as $record)
                                                        <tr wire:key="transaction-record-{{ $record['workspace_id'] }}"
                                                            @click.stop="Livewire.navigate('{{ $record['url'] }}')"
                                                            class="cursor-pointer align-top transition-colors hover:bg-gray-50/70">
                                                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-gray-500">{{ $record['transaction_label'] }}</td>
                                                            <td class="px-4 py-3">
                                                                <div class="flex flex-wrap items-center gap-2">
                                                                    <span class="max-w-[240px] truncate text-gray-800" title="{{ $record['type'] }}">{{ $record['type'] }}</span>
                                                                    @if ($record['issued'])
                                                                        <span class="rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">Issued</span>
                                                                    @elseif ($record['paperless'])
                                                                        <span class="rounded-full bg-[#2A57B4]/10 px-1.5 py-0.5 text-[10px] font-medium text-[#2A57B4]">Paperless</span>
                                                                    @endif
                                                                    @if ($record['deleted_by_student'])
                                                                        <span class="rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-medium text-rose-600">Deleted by student</span>
                                                                    @endif
                                                                </div>
                                                                @if ($record['search_snippet'])
                                                                    <p class="mt-1 max-w-[320px] text-xs italic leading-snug text-gray-400">
                                                                        <span class="font-semibold not-italic text-gray-500">{{ $record['search_snippet_label'] }}:</span>
                                                                        {!! $record['search_snippet'] !!}
                                                                    </p>
                                                                @endif
                                                            </td>
                                                            <td class="whitespace-nowrap px-4 py-3 text-gray-500">{{ $record['date_label'] }}</td>
                                                            <td class="whitespace-nowrap px-4 py-3 {{ $record['paperless'] ? 'text-gray-400' : 'text-gray-500' }}">{{ $record['session_label'] }}</td>
                                                            <td class="whitespace-nowrap px-4 py-3">
                                                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $statusClassFor($record['status']) }}">
                                                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $record['status'] }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @empty
                            <tbody>
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-sm text-gray-400">No transaction records matched your filters.</td>
                                </tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>
            </div>

            {{-- Mobile: expandable student cards --}}
            <div class="space-y-3 md:hidden">
                @forelse ($students as $s)
                    <div wire:key="student-card-{{ $s['key'] }}" x-data="{ open: false }"
                        class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                        <button type="button" @click="open = !open" :aria-expanded="open"
                                class="flex w-full items-start gap-3 p-4 text-left">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900">{{ $s['student_name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $s['student_number'] }} · {{ $s['total'] }} {{ Str::plural('form', $s['total']) }} · {{ $s['latest_label'] }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach ($s['status_counts'] as $status => $count)
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 {{ $statusClassFor($status) }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $count > 1 ? $count . ' ' : '' }}{{ $status }}
                                        </span>
                                    @endforeach
                                </div>
                                @if ($s['search_snippet'])
                                    <p class="mt-2 text-xs italic leading-snug text-gray-400">
                                        <span class="font-semibold not-italic text-gray-500">{{ $s['search_snippet_label'] }}:</span>
                                        {!! $s['search_snippet'] !!}
                                    </p>
                                @endif
                            </div>
                            <svg class="mt-1 h-4 w-4 shrink-0 text-gray-400 transition-transform" :class="open && 'rotate-90'" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak class="divide-y divide-gray-100 border-t border-gray-100 bg-gray-50/60">
                            @foreach ($s['forms'] as $record)
                                <a href="{{ $record['url'] }}" wire:navigate wire:key="m-record-{{ $record['workspace_id'] }}"
                                class="block px-4 py-3.5 transition active:bg-gray-100">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900">{{ $record['type'] }}</p>
                                            <p class="font-mono text-[11px] text-gray-400">{{ $record['transaction_label'] }}</p>
                                        </div>
                                        <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 {{ $statusClassFor($record['status']) }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $record['status'] }}
                                        </span>
                                    </div>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
                                        <span>{{ $record['date_label'] }}</span>
                                        <span class="text-gray-300">·</span>
                                        <span class="{{ $record['paperless'] ? 'text-gray-400' : '' }}">{{ $record['session_label'] }}</span>
                                        @if ($record['issued'])
                                            <span class="rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">Issued</span>
                                        @elseif ($record['paperless'])
                                            <span class="rounded-full bg-[#2A57B4]/10 px-1.5 py-0.5 text-[10px] font-medium text-[#2A57B4]">Paperless</span>
                                        @endif
                                        @if ($record['deleted_by_student'])
                                            <span class="rounded-full bg-rose-50 px-1.5 py-0.5 text-[10px] font-medium text-rose-600">Deleted by student</span>
                                        @endif
                                    </div>
                                    @if ($record['search_snippet'])
                                        <p class="mt-1.5 text-xs italic leading-snug text-gray-400">
                                            <span class="font-semibold not-italic text-gray-500">{{ $record['search_snippet_label'] }}:</span>
                                            {!! $record['search_snippet'] !!}
                                        </p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="rounded-xl border border-dashed border-gray-300 px-4 py-10 text-center text-sm text-gray-400">No transaction records matched your filters.</p>
                @endforelse
            </div>
        {{-- ============ FOLDER VIEW ============ --}}
        @else
            @if ($selectedFolder === null)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @forelse ($folders as $folder)
                        <button type="button" wire:click="openFolder('{{ $folder['type'] }}')"
                            class="group flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 text-left shadow-sm transition hover:border-[#2A57B4]/40 hover:shadow-md">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-100 bg-gray-50">
                                @if ($folder['preview_url'])
                                    <img src="{{ $folder['preview_url'] }}" alt="{{ $folder['type'] }}" class="h-full w-full object-cover">
                                @else
                                    <i class='bx bxs-folder text-2xl text-gray-300'></i>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <h3 class="truncate text-sm font-semibold text-gray-900 group-hover:text-[#2A57B4]">{{ $folder['type'] }}</h3>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $folder['count'] }} {{ Str::plural('transaction', $folder['count']) }}</p>
                                <div class="mt-2 flex flex-wrap gap-1.5 text-[11px] font-medium">
                                    <span class="rounded-full bg-orange-50 px-2 py-0.5 text-orange-700">{{ $folder['pending_count'] }} pending</span>
                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700">{{ $folder['for_appointment_count'] }} appointment</span>
                                    <span class="rounded-full bg-sky-50 px-2 py-0.5 text-sky-700">{{ $folder['in_review_count'] }} in review</span>
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700">{{ $folder['completed_count'] }} completed</span>
                                </div>
                                @if ($folder['search_snippet'])
                                    <p class="mt-2 text-xs italic leading-snug text-gray-400">
                                        <span class="font-semibold not-italic text-gray-500">{{ $folder['search_snippet_label'] }}:</span>
                                        {!! $folder['search_snippet'] !!}
                                    </p>
                                @endif
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-gray-300 transition group-hover:text-[#2A57B4]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.17 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    @empty
                        <p class="col-span-full rounded-xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-400">No document types matched your filters.</p>
                    @endforelse
                </div>
            @else
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <button type="button" wire:click="closeFolder"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition hover:bg-gray-50">
                        <i class='bx bx-arrow-back'></i> All folders
                    </button>
                    <h3 class="text-sm font-semibold text-gray-900">{{ $selectedFolder }} <span class="font-normal text-gray-400">({{ $totalCount }})</span></h3>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($records as $record)
                        <a href="{{ $record['url'] }}" wire:navigate wire:key="folder-item-{{ $record['workspace_id'] }}"
                           class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:border-[#2A57B4]/40 hover:shadow-md">
                            <div class="border-b border-gray-100 bg-gray-50 p-3">
                                @if ($record['paperless'])
                                    <div class="flex h-40 flex-col items-center justify-center gap-1 rounded-lg bg-white text-center ring-1 ring-gray-200">
                                        <i class='bx bx-file text-4xl text-[#2A57B4]/60'></i>
                                        @if ($record['issued'])
                                            <span class="rounded-full bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">Issued</span>
                                        @endif
                                        <p class="text-sm font-semibold text-gray-700">{{ $record['type'] }}</p>
                                        <p class="px-3 text-xs text-gray-400">{{ $record['summary'] }}</p>
                                    </div>
                                @else
                                    @include('livewire.partials.document-preview-card', ['workspace' => $record['workspace']])
                                @endif
                            </div>
                            <div class="space-y-2 p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-gray-900 group-hover:text-[#2A57B4]">{{ $record['student_name'] }}</p>
                                        <p class="text-xs text-gray-400">{{ $record['transaction_label'] }} · {{ $record['student_number'] }}</p>
                                    </div>
                                    <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-medium ring-1 {{ $statusClassFor($record['status']) }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $record['status'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-400">{{ $record['date_label'] }} · {{ $record['session_label'] }}</p>
                                @if ($record['search_snippet'])
                                    <p class="text-xs italic leading-snug text-gray-400">
                                        <span class="font-semibold not-italic text-gray-500">{{ $record['search_snippet_label'] }}:</span>
                                        {!! $record['search_snippet'] !!}
                                    </p>
                                @endif
                            </div>
                        </a>
                    @empty
                        <p class="col-span-full rounded-xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-400">No transactions in this folder.</p>
                    @endforelse
                </div>
            @endif
        @endif

        {{-- Pagination --}}
        @if (($viewMode === 'table' || $selectedFolder !== null) && $totalCount > 0)
            <div class="mb-12 mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-xs text-gray-500">
                    Showing {{ (($currentPage - 1) * $perPage) + 1 }}–{{ min($currentPage * $perPage, $totalCount) }} of {{ number_format($totalCount) }}
                </p>
                <div class="flex w-full overflow-hidden rounded-lg border border-gray-200 bg-white text-sm shadow-sm sm:inline-flex sm:w-auto">
                    <button type="button" wire:click="previousPage" @disabled($currentPage === 1) aria-label="Previous page"
                        class="px-3.5 py-2 transition {{ $currentPage === 1 ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:bg-gray-50' }}">
                        <i class='bx bx-chevron-left'></i>
                    </button>
                    <div class="flex items-center border-x border-gray-200 px-4 text-gray-600">{{ $currentPage }} / {{ $totalPages }}</div>
                    <button type="button" wire:click="nextPage({{ $totalPages }})" @disabled($currentPage === $totalPages) aria-label="Next page"
                        class="px-3.5 py-2 transition {{ $currentPage === $totalPages ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:bg-gray-50' }}">
                        <i class='bx bx-chevron-right'></i>
                    </button>
                </div>
            </div>
        @endif

    @endif
</div>