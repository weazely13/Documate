@php
    $field = 'w-full rounded-xl border-gray-200 bg-white py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4] sm:text-sm';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700';
    $pill = 'shrink-0 rounded-full border-gray-200 bg-gray-50 py-1.5 pl-3 pr-8 text-xs font-medium text-gray-700 focus:border-[#2A57B4] focus:ring-[#2A57B4]';

    $verificationBadge = fn (string $s) => match ($s) {
        'verified' => 'bg-emerald-50 text-emerald-700',
        'pending' => 'bg-amber-50 text-amber-700',
        'rejected' => 'bg-rose-50 text-rose-700',
        default => 'bg-gray-100 text-gray-600',
    };

    [$periodState, $periodClass] = ! $currentPeriod
        ? ['None yet', 'bg-gray-100 text-gray-600']
        : ($currentPeriod->isExpired()
            ? ['Closed', 'bg-gray-100 text-gray-600']
            : ($currentPeriod->isOpen() ? ['Open', 'bg-emerald-50 text-emerald-700'] : ['Scheduled', 'bg-blue-50 text-blue-700']));
@endphp

<x-admin.users-manager active="verifications">
<div class="space-y-5">

    {{-- Verification period --}}
    <section class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-200/70 bg-white p-4 shadow-sm cq-sm:p-5" aria-labelledby="period-title">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 id="period-title" class="text-base font-semibold text-gray-900">Verification period</h3>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $periodClass }}">{{ $periodState }}</span>
            </div>
            <p class="mt-0.5 text-sm text-gray-500">
                {{ $currentPeriod ? $currentPeriod->label() : 'No verification period has been raised yet.' }}
            </p>
        </div>
        <button type="button" wire:click="openRaise"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-[#2A57B4] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#24499A]">
            <i class='bx bx-plus text-lg' aria-hidden="true"></i>Raise verification
        </button>
    </section>

    @if ($showRaiseModal)
        <x-admin.form-modal title="Raise verification"
            subtitle="Every student and officer is asked to verify their account by the end date, and everyone is notified."
            submit="raiseVerification" close="closeRaise" width="sm:max-w-md">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="v-start" class="{{ $label }}">Start date</label>
                    <input id="v-start" type="date" wire:model="verification_start_date" class="{{ $field }}">
                    @error('verification_start_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="v-end" class="{{ $label }}">End date</label>
                    <input id="v-end" type="date" wire:model="verification_end_date" class="{{ $field }}">
                    @error('verification_end_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <x-slot:footer>
                <button type="button" wire:click="closeRaise"
                    class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50">Cancel</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="raiseVerification"
                    onclick="return confirm('Raise a new verification period? All students and officers will be notified.')"
                    class="rounded-lg bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A] disabled:opacity-60">
                    Raise verification
                </button>
            </x-slot:footer>
        </x-admin.form-modal>
    @endif

    {{-- Students --}}
    <section class="overflow-hidden rounded-2xl border border-gray-200/70 bg-white shadow-sm" aria-label="Students">
        <div class="flex flex-col gap-2 border-b border-gray-100 p-2.5 cq-md:flex-row cq-md:items-center">
            <div class="relative min-w-0 cq-md:flex-1">
                <i class='bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400' aria-hidden="true"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, student number or email" aria-label="Search students"
                    class="w-full rounded-full border-gray-200 bg-gray-50 py-2 pl-9 pr-3 text-base focus:border-[#2A57B4] focus:ring-[#2A57B4] cq-sm:text-sm">
            </div>
            <div class="flex min-w-0 items-center gap-1.5 overflow-x-auto [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                <select wire:model.live="verificationStatusFilter" aria-label="Verification status" class="{{ $pill }}">
                    <option value="">Any verification</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                    <option value="not_submitted">Not submitted</option>
                </select>
                <select wire:model.live="accountStatusFilter" aria-label="Account status" class="{{ $pill }}">
                    <option value="">Any account</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        {{-- Wide: table --}}
        <div class="hidden overflow-x-auto cq-lg:block">
            <table class="w-full text-sm">
                <thead class="text-xs font-medium text-gray-500">
                    <tr class="border-b border-gray-100">
                        <th class="px-5 py-3 text-left font-medium">Student</th>
                        <th class="px-4 py-3 text-left font-medium">Role</th>
                        <th class="px-4 py-3 text-left font-medium">Verification</th>
                        <th class="px-4 py-3 text-left font-medium">Account</th>
                        <th class="px-5 py-3 text-right font-medium"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($records as $record)
                        <tr wire:key="v-{{ $record['user_id'] }}" class="transition hover:bg-gray-50/70">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-gray-900">{{ $record['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $record['student_number'] }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-gray-700">{{ $record['role'] }}</td>
                            <td class="px-4 py-3.5">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $verificationBadge($record['verification_status']) }}">
                                    {{ ucfirst(str_replace('_', ' ', $record['verification_status'])) }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center gap-1.5 text-sm {{ $record['account_status'] === 'active' ? 'text-gray-700' : 'text-rose-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $record['account_status'] === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>{{ ucfirst($record['account_status']) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center justify-end gap-2">
                                    <button wire:click="viewHistory({{ $record['user_id'] }})"
                                        class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-200">
                                        History ({{ $record['history_count'] }})
                                    </button>
                                    <button wire:click="toggleAccountStatus({{ $record['user_id'] }})"
                                        onclick="return confirm('{{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }} this account?')"
                                        class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ $record['account_status'] === 'active' ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' }}">
                                        {{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-14 text-center text-sm text-gray-500">No students match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Narrow: one row per student --}}
        <ul class="divide-y divide-gray-100 cq-lg:hidden">
            @forelse($records as $record)
                <li wire:key="vm-{{ $record['user_id'] }}" class="px-4 py-3.5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold text-gray-900">{{ $record['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $record['student_number'] }} · {{ $record['role'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $verificationBadge($record['verification_status']) }}">
                            {{ ucfirst(str_replace('_', ' ', $record['verification_status'])) }}
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="mr-auto inline-flex items-center gap-1.5 text-xs {{ $record['account_status'] === 'active' ? 'text-gray-600' : 'text-rose-700' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $record['account_status'] === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>{{ ucfirst($record['account_status']) }}
                        </span>
                        <button wire:click="viewHistory({{ $record['user_id'] }})"
                            class="rounded-full bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-700">History ({{ $record['history_count'] }})</button>
                        <button wire:click="toggleAccountStatus({{ $record['user_id'] }})"
                            onclick="return confirm('{{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }} this account?')"
                            class="rounded-full px-3 py-2 text-xs font-semibold {{ $record['account_status'] === 'active' ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700' }}">
                            {{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </li>
            @empty
                <li class="px-4 py-12 text-center text-sm text-gray-500">No students match these filters.</li>
            @endforelse
        </ul>

        @if($totalPages > 1)
            <div class="flex items-center justify-between gap-2 border-t border-gray-100 px-4 py-3">
                <button wire:click="$set('currentPage', {{ max(1, $currentPage - 1) }})" @disabled($currentPage === 1)
                    class="rounded-full px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 disabled:text-gray-300 disabled:hover:bg-transparent">Previous</button>
                <span class="text-sm text-gray-500">Page {{ $currentPage }} of {{ $totalPages }}</span>
                <button wire:click="$set('currentPage', {{ min($totalPages, $currentPage + 1) }})" @disabled($currentPage === $totalPages)
                    class="rounded-full px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100 disabled:text-gray-300 disabled:hover:bg-transparent">Next</button>
            </div>
        @endif
    </section>

    {{-- History: teleported out of the layout so it covers the whole screen --}}
    @if($showHistoryModal)
        @teleport('body')
        <div class="fixed inset-0 z-[9999] flex items-end justify-center bg-gray-900/50 backdrop-blur-sm sm:items-center sm:p-4"
             wire:click.self="closeHistory" role="dialog" aria-modal="true" aria-labelledby="history-title">
            <div class="max-h-[88dvh] w-full max-w-2xl overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 id="history-title" class="text-lg font-bold text-gray-900">Verification history</h3>
                        @if ($historyUser)
                            <p class="text-sm text-gray-500">{{ $historyUser->first_name }} {{ $historyUser->last_name }}</p>
                        @endif
                    </div>
                    <button wire:click="closeHistory" aria-label="Close" class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100">
                        <i class='bx bx-x text-2xl' aria-hidden="true"></i>
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse($historyRecords as $entry)
                        <div class="rounded-xl bg-gray-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $verificationBadge($entry->status) }}">{{ ucfirst($entry->status) }}</span>
                                    <p class="mt-1.5 text-sm text-gray-600">{{ $entry->semester }} semester · AY {{ $entry->academic_year }}</p>
                                </div>
                                <p class="text-xs text-gray-500">{{ $entry->created_at?->format('M j, Y g:i A') }}</p>
                            </div>
                            @if($entry->e_slip_path)
                                <a href="{{ asset('storage/' . $entry->e_slip_path) }}" target="_blank" rel="noopener"
                                   class="mt-2 inline-block text-sm font-semibold text-[#2A57B4] hover:underline">View e-slip</a>
                            @endif
                            @if(!empty($entry->ocr_data))
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-sm font-medium text-gray-600">Scanned details</summary>
                                    <pre class="mt-1 overflow-x-auto rounded-lg bg-white p-2 text-xs">{{ json_encode($entry->ocr_data, JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-gray-500">No submissions on file.</p>
                    @endforelse
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
</x-admin.users-manager>
