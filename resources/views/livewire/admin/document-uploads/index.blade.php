<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Document Uploads</h1>
        <p class="text-sm text-slate-500">Transactions with an attended appointment awaiting a verification photo.</p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search student, ID, or document..."
                class="w-full max-w-sm rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:bg-white">

            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Date range filter --}}
                <div class="flex items-center gap-1.5 rounded-xl border border-slate-200 bg-slate-50 px-2.5 py-1.5">
                    <i class='bx bx-calendar text-sm text-slate-400'></i>
                    <input type="date" wire:model.live="dateFrom"
                        class="border-0 bg-transparent text-xs text-slate-600 outline-none focus:ring-0">
                    <span class="text-xs text-slate-400">to</span>
                    <input type="date" wire:model.live="dateTo"
                        class="border-0 bg-transparent text-xs text-slate-600 outline-none focus:ring-0">
                    @if($dateFrom !== '' || $dateTo !== '')
                        <button type="button" wire:click="clearDateFilter" class="ml-1 text-slate-400 hover:text-slate-600" title="Clear date filter">
                            <i class='bx bx-x text-base'></i>
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 p-1">
                    @foreach(['needs_action' => 'Needs Action', 'verified' => 'Verified', 'all' => 'All'] as $key => $label)
                        <button type="button" wire:click="$set('filter', '{{ $key }}')"
                            class="rounded-lg px-3.5 py-1.5 text-sm font-semibold transition {{ $filter === $key ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Student</th>
                        <th class="px-4 py-3">Forms</th>
                        <th class="px-4 py-3">Latest Attended</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>

                @forelse($students as $s)
                    {{-- One <tbody> per student so the expand state stays with that student --}}
                    <tbody wire:key="student-{{ $s['user_id'] ?? 'unknown' }}"
                        x-data="{ open: false }"
                        class="border-t border-slate-100 first:border-t-0">

                        {{-- Student row (one per student) --}}
                        <tr @click="open = !open" class="cursor-pointer hover:bg-slate-50">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <i class='bx bx-chevron-right text-lg text-slate-400 transition-transform'
                                    :class="open && 'rotate-90'"></i>
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $s['student_name'] }}</p>
                                        <p class="text-xs text-slate-400">{{ $s['student_number'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-slate-600">
                                {{ $s['total'] }} {{ Str::plural('form', $s['total']) }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-500">
                                {{ $s['latest_attended']?->format('M j, Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3.5">
                                @if($s['needs_action'] > 0)
                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">
                                        {{ $s['needs_action'] }} need action
                                    </span>
                                @elseif($s['all_verified'])
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                        All verified
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                        Reviewing
                                    </span>
                                @endif
                            </td>
                        </tr>

                        {{-- Expanded: this student's forms, latest first --}}
                        <tr x-show="open" x-cloak>
                            <td colspan="4" class="bg-slate-50/70 px-4 py-2">
                                <ul class="divide-y divide-slate-200/70">
                                    @foreach($s['forms'] as $r)
                                        @php
                                            $badge = match ($r['doc_status']) {
                                                'Verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                                'Needs Re-upload' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                                'Reviewing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                                default => 'bg-amber-50 text-amber-700 ring-amber-200',
                                            };
                                        @endphp
                                        <li wire:key="doc-upload-{{ $r['workspace_id'] }}">
                                            <a href="{{ route('admin.document-uploads.show', $r['workspace_id']) }}" wire:navigate
                                                class="flex items-center justify-between gap-3 rounded-lg py-2.5 pl-8 pr-2 hover:bg-white">
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium text-slate-800">{{ $r['type'] }}</p>
                                                    <p class="text-xs text-slate-400">
                                                        Attended {{ $r['attended_at']?->format('M j, Y g:i A') ?? '—' }}
                                                    </p>
                                                </div>
                                                <span class="inline-flex shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $badge }}">
                                                    {{ $r['doc_status'] }}
                                                </span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    </tbody>
                @empty
                    <tbody>
                        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">
                            No records match your filters.
                        </td></tr>
                    </tbody>
                @endforelse
            </table>
        </div>
    </div>
</div>