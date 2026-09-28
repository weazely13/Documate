<div class="mx-auto max-w-7xl space-y-6">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-slate-900">Student Verification</h2>
            <p class="mt-0.5 text-xs text-slate-500">Manage verification periods and student verification status.</p>
        </div>
    </div>

    {{-- CURRENT PERIOD + RAISE VERIFICATION --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Current Verification Period</p>
                @if($currentPeriod)
                    <h3 class="text-base font-semibold text-slate-900">
                        {{ $currentPeriod->label() }}
                        <span class="ml-1 text-xs font-medium text-slate-500">
                            · {{ $currentPeriod->isExpired() ? 'Closed' : ($currentPeriod->isOpen() ? 'Open' : 'Scheduled') }}
                        </span>
                    </h3>
                @else
                    <p class="mt-0.5 text-xs text-slate-400">No verification period has been raised yet.</p>
                @endif
            </div>
        </div>

        <form wire:submit.prevent="raiseVerification" class="mt-4 flex flex-wrap items-end gap-3 border-t border-slate-100 pt-4">
            <div class="w-full sm:w-auto">
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Start date</label>
                <input type="date" wire:model="verification_start_date"
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @error('verification_start_date') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div class="w-full sm:w-auto">
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">End date</label>
                <input type="date" wire:model="verification_end_date"
                    class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                @error('verification_end_date') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                onclick="return confirm('Raise a new verification period? All students and officers will be notified.')"
                class="rounded-xl bg-[#2A57B4] px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                Raise Verification
            </button>
        </form>
    </div>

    {{-- FILTERS --}}
    <div class="flex flex-wrap items-end gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="min-w-[200px] flex-1">
            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Search Student</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, student no., email..."
                class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
        </div>

        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Verification</label>
            <div class="relative">
                <select wire:model.live="verificationStatusFilter"
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-3.5 pr-9 text-xs focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">All statuses</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                    <option value="not_submitted">Not submitted</option>
                </select>
                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>

        <div class="w-full sm:w-auto">
            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Account</label>
            <div class="relative">
                <select wire:model.live="accountStatusFilter"
                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2 pl-3.5 pr-9 text-xs focus:border-[#2A57B4] focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>
    </div>

    {{-- STUDENT LIST --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Student No.</th>
                        <th class="px-4 py-3 text-left">Role</th>
                        <th class="px-4 py-3 text-left">Verification</th>
                        <th class="px-4 py-3 text-left">Account</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($records as $record)
                        <tr class="transition hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $record['name'] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $record['student_number'] }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $record['role'] }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $record['verification_status'] === 'verified',
                                    'bg-amber-50 text-amber-700' => $record['verification_status'] === 'pending',
                                    'bg-rose-50 text-rose-600' => $record['verification_status'] === 'rejected',
                                    'bg-slate-100 text-slate-500' => $record['verification_status'] === 'not_submitted',
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $record['verification_status'])) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $record['account_status'] === 'active',
                                    'bg-rose-50 text-rose-600' => $record['account_status'] === 'inactive',
                                ])>
                                    {{ ucfirst($record['account_status']) }}
                                </span>
                            </td>
                            <td class="space-x-1.5 px-4 py-3 text-right">
                                <button wire:click="viewHistory({{ $record['user_id'] }})"
                                    class="rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-[#2A57B4] hover:text-[#2A57B4]">
                                    History ({{ $record['history_count'] }})
                                </button>
                                <button
                                    wire:click="toggleAccountStatus({{ $record['user_id'] }})"
                                    onclick="return confirm('{{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }} this account?')"
                                    class="rounded-lg border px-2.5 py-1 text-[11px] font-semibold transition
                                        {{ $record['account_status'] === 'active'
                                            ? 'border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100'
                                            : 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                    {{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-xs text-slate-400">No students match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($totalPages > 1)
        <div class="flex items-center justify-center gap-2">
            <button wire:click="$set('currentPage', {{ max(1, $currentPage - 1) }})"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-[#2A57B4] hover:text-[#2A57B4]">Prev</button>
            <span class="px-2 text-xs font-semibold text-slate-500">{{ $currentPage }} / {{ $totalPages }}</span>
            <button wire:click="$set('currentPage', {{ min($totalPages, $currentPage + 1) }})"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-[#2A57B4] hover:text-[#2A57B4]">Next</button>
        </div>
    @endif

    {{-- HISTORY MODAL --}}
    @if($showHistoryModal)
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-md" wire:click.self="closeHistory">
            <div class="max-h-[80vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-slate-900">
                        Verification History{{ $historyUser ? ' — ' . $historyUser->first_name . ' ' . $historyUser->last_name : '' }}
                    </h3>
                    <button wire:click="closeHistory" class="text-xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
                </div>

                @forelse($historyRecords as $entry)
                    <div class="mb-3 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <span @class([
                                    'rounded-full px-2 py-0.5 text-[10px] font-semibold',
                                    'bg-emerald-50 text-emerald-700' => $entry->status === 'verified',
                                    'bg-amber-50 text-amber-700' => $entry->status === 'pending',
                                    'bg-rose-50 text-rose-600' => $entry->status === 'rejected',
                                ])>{{ ucfirst($entry->status) }}</span>
                                <p class="mt-1.5 text-xs text-slate-500">
                                    {{ $entry->semester }} — AY {{ $entry->academic_year }}
                                </p>
                            </div>
                            <p class="text-[11px] text-slate-400">{{ $entry->created_at?->format('M j, Y g:i A') }}</p>
                        </div>

                        @if($entry->e_slip_path)
                            <a href="{{ asset('storage/' . $entry->e_slip_path) }}" target="_blank"
                                class="mt-2 inline-block text-xs font-semibold text-[#2A57B4] hover:underline">
                                View e-slip
                            </a>
                        @endif

                        @if(!empty($entry->ocr_data))
                            <details class="mt-2">
                                <summary class="cursor-pointer text-xs font-semibold text-slate-500">OCR data</summary>
                                <pre class="mt-1 overflow-x-auto rounded-lg bg-white p-2 text-xs">{{ json_encode($entry->ocr_data, JSON_PRETTY_PRINT) }}</pre>
                            </details>
                        @endif
                    </div>
                @empty
                    <p class="py-8 text-center text-xs text-slate-400">No submissions on file.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>