@php
    $homeUrl = \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/');
    $fieldClass = 'rounded-lg border-gray-300 py-2.5 text-base shadow-sm sm:py-2 sm:text-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]';

    $dotFor = fn (string $result) => match ($result) {
        'completed' => 'bg-emerald-600',
        'closed' => 'bg-slate-400',
        default => 'bg-[#2A57B4]',
    };
@endphp

<div class="mx-auto max-w-5xl">
    @if ($activeTab === 'logbooks')

        <x-admin.page-header
            title="Office Logbooks"
            description="VPSD office logbooks uploaded from Excel exports."
            :crumbs="['Admin' => $homeUrl, 'Transaction Records' => route('admin.transactions.index'), 'Logbooks' => null]">
            <x-slot:actions>
                <button type="button" wire:click="setActiveTab('transactions')"
                        class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]">&larr; Transaction records</button>
            </x-slot:actions>
        </x-admin.page-header>

        <livewire:admin.logbook-manager />

    @else

        <x-admin.page-header
            title="Transaction Records"
            description="Every transaction, paperless and paper-based. Look-up only: open ones are handled in Workflow Review and Appointments."
            :crumbs="['Admin' => $homeUrl, 'Transaction Records' => null]">
            <x-slot:actions>
                <button type="button" wire:click="setActiveTab('logbooks')"
                        class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]">Office logbooks &rarr;</button>
            </x-slot:actions>
        </x-admin.page-header>

        {{-- Search + two filters --}}
        <div class="mb-5 flex flex-col gap-2 sm:flex-row">
            <div class="relative sm:flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.2-5.2m0 0A7.5 7.5 0 1 0 5.2 5.2a7.5 7.5 0 0 0 10.6 10.6Z" />
                </svg>
                <input type="search" wire:model.live.debounce.300ms="search" aria-label="Search records"
                       placeholder="Search name, student number, document or code"
                       class="w-full pl-9 pr-3 {{ $fieldClass }}">
            </div>
            <select wire:model.live="result" aria-label="Result" class="sm:w-56 {{ $fieldClass }}">
                @foreach ($results as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <select wire:model.live="period" aria-label="Period" class="sm:w-40 {{ $fieldClass }}">
                @foreach ($periods as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Table (md and up): one header row per student, their transactions under it --}}
        <div class="hidden overflow-hidden rounded-2xl border border-gray-200/70 bg-white md:block">
            <table class="w-full text-sm">
                <thead class="border-b border-gray-100 text-xs font-medium text-gray-500">
                    <tr>
                        <th class="px-6 py-3 text-left font-medium">Document</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-left font-medium">Result</th>
                    </tr>
                </thead>
                @forelse ($students as $s)
                    {{-- Collapsed by default; opened when searching so matches are visible. The key resets it per search. --}}
                    <tbody wire:key="student-{{ $s['key'] }}-{{ md5($search) }}" x-data="{ open: @js(trim($search) !== '') }"
                           class="border-t border-gray-100 first-of-type:border-t-0">
                        <tr>
                            <th colspan="3" scope="rowgroup" class="p-0 text-left font-normal">
                                <button type="button" @click="open = !open" :aria-expanded="open"
                                        class="flex w-full items-center gap-3 px-6 py-3.5 text-left transition hover:bg-gray-50">
                                    <i class='bx bx-chevron-right text-xl text-gray-400 transition-transform' :class="open && 'rotate-90'" aria-hidden="true"></i>
                                    <span class="min-w-0 flex-1">
                                        <span class="font-semibold text-gray-900">{{ $s['student_name'] }}</span>
                                        <span class="ml-2 text-[13px] text-gray-500">{{ $s['student_number'] }}</span>
                                    </span>
                                    <span class="shrink-0 text-[13px] text-gray-500">
                                        {{ $s['forms']->count() }} {{ Str::plural('transaction', $s['forms']->count()) }}
                                        @if ($s['open_count']) · <span class="text-[#2A57B4]">{{ $s['open_count'] }} still open</span> @endif
                                        · {{ $s['latest_label'] }}
                                    </span>
                                </button>
                            </th>
                        </tr>
                        @foreach ($s['forms'] as $record)
                            <tr wire:key="record-{{ $record['workspace_id'] }}" x-show="open" x-cloak
                                onclick="Livewire.navigate('{{ $record['url'] }}')"
                                class="cursor-pointer align-top bg-gray-50/50 transition-colors hover:bg-gray-100/70">
                                <td class="py-3.5 pl-[3.25rem] pr-4">
                                    <a href="{{ $record['url'] }}" wire:navigate onclick="event.stopPropagation()"
                                       class="block max-w-[420px] truncate text-gray-900 hover:text-[#2A57B4]" title="{{ $record['type'] }}">{{ $record['type'] }}</a>
                                    <p class="mt-0.5 text-[13px] text-gray-500">
                                        {{ $record['paperless'] ? 'Online' : 'Paper' }}
                                        @if ($record['issuer']) · issued by <span class="text-gray-700">{{ $record['issuer'] }}</span> @endif
                                        @if ($record['deleted_by_student']) · <span class="text-rose-600">deleted by student</span> @endif
                                    </p>
                                    @if ($record['search_snippet'])
                                        <p class="mt-1 max-w-[420px] text-xs leading-snug text-gray-500">
                                            <span class="font-semibold">{{ $record['search_snippet_label'] }}:</span>
                                            {!! $record['search_snippet'] !!}
                                        </p>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-gray-500">{{ $record['date_label'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <span class="inline-flex items-center gap-2 text-gray-700">
                                        <span class="h-2 w-2 rounded-full {{ $dotFor($record['result']) }}" aria-hidden="true"></span>
                                        {{ $record['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                @empty
                    <tbody>
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center text-sm text-gray-500">No records match these filters.</td>
                        </tr>
                    </tbody>
                @endforelse
            </table>
        </div>

        {{-- Cards (phones): one card per student --}}
        <div class="space-y-3 md:hidden">
            @forelse ($students as $s)
                <section wire:key="m-student-{{ $s['key'] }}-{{ md5($search) }}" x-data="{ open: @js(trim($search) !== '') }"
                         class="overflow-hidden rounded-2xl border border-gray-200/70 bg-white">
                    <button type="button" @click="open = !open" :aria-expanded="open"
                            class="flex w-full items-center gap-3 px-4 py-3.5 text-left active:bg-gray-50">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-gray-900">{{ $s['student_name'] }}</span>
                            <span class="block text-xs text-gray-500">
                                {{ $s['student_number'] }} · {{ $s['forms']->count() }} {{ Str::plural('transaction', $s['forms']->count()) }}
                                @if ($s['open_count']) · <span class="text-[#2A57B4]">{{ $s['open_count'] }} open</span> @endif
                            </span>
                        </span>
                        <i class='bx bx-chevron-down shrink-0 text-xl text-gray-400 transition-transform' :class="open && 'rotate-180'" aria-hidden="true"></i>
                    </button>
                    <div x-show="open" x-cloak class="divide-y divide-gray-100 border-t border-gray-100 bg-gray-50/50">
                        @foreach ($s['forms'] as $record)
                            <a href="{{ $record['url'] }}" wire:navigate wire:key="m-record-{{ $record['workspace_id'] }}"
                               class="block px-4 py-3.5 transition active:bg-gray-50">
                                <p class="text-sm text-gray-900">{{ $record['type'] }}</p>
                                <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-gray-500">
                                    <span class="inline-flex items-center gap-1.5 text-gray-700">
                                        <span class="h-2 w-2 rounded-full {{ $dotFor($record['result']) }}" aria-hidden="true"></span>{{ $record['status'] }}
                                    </span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ $record['date_label'] }}</span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ $record['paperless'] ? 'Online' : 'Paper' }}</span>
                                    @if ($record['deleted_by_student']) <span class="text-rose-600">· deleted by student</span> @endif
                                </p>
                                @if ($record['issuer'])
                                    <p class="mt-0.5 text-xs text-gray-500">Issued by <span class="text-gray-700">{{ $record['issuer'] }}</span></p>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="rounded-2xl border border-dashed border-gray-300 px-4 py-12 text-center text-sm text-gray-500">No records match these filters.</p>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($totalCount > 0)
            <div class="mb-12 mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row">
                <p class="text-xs text-gray-500">
                    {{ number_format($recordCount) }} {{ Str::plural('transaction', $recordCount) }} from
                    {{ number_format($totalCount) }} {{ Str::plural('student', $totalCount) }}
                    @if ($totalPages > 1) · page {{ $currentPage }} of {{ $totalPages }} @endif
                </p>
                @if ($totalPages > 1)
                    <div class="flex gap-2">
                        <button type="button" wire:click="previousPage" @disabled($currentPage === 1)
                            class="rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm transition {{ $currentPage === 1 ? 'cursor-not-allowed text-gray-300' : 'text-gray-700 hover:bg-gray-50' }}">Previous</button>
                        <button type="button" wire:click="nextPage({{ $totalPages }})" @disabled($currentPage === $totalPages)
                            class="rounded-lg border border-gray-200 bg-white px-3.5 py-2 text-sm transition {{ $currentPage === $totalPages ? 'cursor-not-allowed text-gray-300' : 'text-gray-700 hover:bg-gray-50' }}">Next</button>
                    </div>
                @endif
            </div>
        @endif

    @endif
</div>
