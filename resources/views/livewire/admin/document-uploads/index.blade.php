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
                        <th class="px-4 py-3">Document</th>
                        <th class="px-4 py-3">Attended</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $r)
                        @php
                            $badge = match ($r['doc_status']) {
                                'Verified' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                'Needs Re-upload' => 'bg-rose-50 text-rose-700 ring-rose-200',
                                'Reviewing' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                default => 'bg-amber-50 text-amber-700 ring-amber-200',
                            };
                        @endphp
                        <tr wire:key="doc-upload-{{ $r['workspace_id'] }}"
                            @click="Livewire.navigate('{{ route('admin.document-uploads.show', $r['workspace_id']) }}')"
                            class="cursor-pointer hover:bg-slate-50">
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-slate-900">{{ $r['student_name'] }}</p>
                                <p class="text-xs text-slate-400">{{ $r['student_number'] }}</p>
                            </td>
                            <td class="px-4 py-3.5">{{ $r['type'] }}</td>
                            <td class="px-4 py-3.5 text-slate-500">{{ $r['attended_at']?->format('M j, Y') ?? '—' }}</td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $badge }}">{{ $r['doc_status'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">
                            No records match your filters.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>