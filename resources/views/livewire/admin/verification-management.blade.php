@php
    $field = 'w-full rounded-lg border-slate-300 bg-white py-2 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';
    $label = 'mb-1.5 block text-sm font-medium text-slate-700';
    $pill = 'h-9 shrink-0 rounded-lg border-slate-300 bg-white py-0 pl-3 pr-8 text-sm text-slate-700 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20';

    // Phones: each filter is a 36px icon with the native dropdown laid over it; from sm up, a normal dropdown.
    $pickWrap = 'relative grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 sm:block sm:h-auto sm:w-auto sm:border-0 sm:bg-transparent max-sm:[@media(pointer:coarse)]:h-11 max-sm:[@media(pointer:coarse)]:w-11';
    $pickField = 'absolute inset-0 h-full w-full cursor-pointer opacity-0 sm:static sm:h-9 sm:w-auto sm:max-w-[200px] sm:opacity-100 sm:[@media(pointer:coarse)]:h-11 rounded-lg border-slate-300 bg-white py-0 pl-3 pr-8 text-sm text-slate-700 shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20';
    $verificationBadge = fn (string $s) => match ($s) {
        'verified' => 'bg-emerald-50 text-emerald-700',
        'pending' => 'bg-amber-50 text-amber-700',
        'rejected' => 'bg-rose-50 text-rose-700',
        default => 'bg-slate-100 text-slate-600',
    };

    [$periodState, $periodClass] = ! $currentPeriod
        ? ['None yet', 'bg-slate-100 text-slate-600']
        : ($currentPeriod->isExpired()
            ? ['Closed', 'bg-slate-100 text-slate-600']
            : ($currentPeriod->isOpen() ? ['Open', 'bg-emerald-50 text-emerald-700'] : ['Scheduled', 'bg-blue-50 text-blue-700']));
@endphp

{{-- Shown inside Users and accounts > Accounts (ManageUsers, ?view=verifications). --}}
<div class="space-y-5">

    {{-- Verification period --}}
    <section class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm cq-sm:p-5" aria-labelledby="period-title">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <h3 id="period-title" class="text-base font-semibold text-slate-900">Verification period</h3>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $periodClass }}">{{ $periodState }}</span>
            </div>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $currentPeriod ? $currentPeriod->label() : 'No verification period has been raised yet.' }}
            </p>
        </div>
        <button type="button" wire:click="openRaise"
            class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg bg-[#2A57B4] px-3.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-[#24499A]">
            <i class='bx bx-plus text-lg' aria-hidden="true"></i>Raise verification
        </button>
    </section>

    @if ($showRaiseModal)
        <x-admin.form-modal title="Raise verification"
            subtitle="Every student and officer is asked to verify their account by the end date, and everyone is notified."
            submit="raiseVerification" close="closeRaise" width="sm:max-w-md">
            @php $raiseSemester = \App\Models\Semester::current(); @endphp
            @if ($raiseSemester)
                <p class="mb-4 flex items-start gap-2 rounded-lg bg-blue-50 px-3 py-2 text-xs leading-relaxed text-slate-700 ring-1 ring-inset ring-blue-100">
                    <i class="bx bx-calendar mt-px text-sm text-[#2A57B4]" aria-hidden="true"></i>
                    <span>For <b>{{ $raiseSemester->label() }}</b>{{ $raiseSemester->isDated() ? ', which runs ' . $raiseSemester->dateLabel() . '. Both dates must fall inside it.' : '. Add this semester’s dates on the Semesters page to keep periods inside it.' }}</span>
                </p>
            @endif
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
                    class="rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" wire:loading.attr="disabled" wire:target="raiseVerification"
                    onclick="return confirm('Raise a new verification period? All students and officers will be notified.')"
                    class="rounded-lg bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A] disabled:opacity-60">
                    Raise verification
                </button>
            </x-slot:footer>
        </x-admin.form-modal>
    @endif

    {{-- Students: one row, as on Workflow review (Filters, then the search), then the table --}}
    @php
        $wfField = 'rounded-lg border-slate-300 bg-white text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm';
        $fsel = 'h-9 w-full py-0 text-sm [@media(pointer:coarse)]:h-11 ' . $wfField;
        $vActive = (int) filled($verificationStatusFilter) + (int) filled($accountStatusFilter);
    @endphp
    <section class="space-y-3" aria-label="Students">
        <div class="flex items-center gap-2">
            <x-admin.filters-popup :count="$vActive" reset="resetFilters">
                <label class="col-span-2 block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Verification</span>
                    <select wire:model.live="verificationStatusFilter" class="{{ $fsel }}">
                        <option value="">Any verification</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                        @endforeach
                        <option value="not_submitted">Not submitted</option>
                    </select>
                </label>
                <label class="col-span-2 block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Account</span>
                    <select wire:model.live="accountStatusFilter" class="{{ $fsel }}">
                        <option value="">Any account</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </label>
            </x-admin.filters-popup>
            <div class="relative min-w-0 flex-1">
                <i class='bx bx-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base text-slate-400' aria-hidden="true"></i>
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search name, student number or email" aria-label="Search students"
                       class="h-9 w-full pl-9 pr-3 [@media(pointer:coarse)]:h-11 {{ $wfField }}">
            </div>
        </div>

        {{-- Table: the Workflow review table --}}
        <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white transition-opacity cq-md:block"
             wire:loading.class="opacity-60" wire:target="search, verificationStatusFilter, accountStatusFilter, resetFilters, currentPage">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="border-b border-slate-200 text-xs">
                        <tr>
                            <th scope="col" class="w-12 py-2.5 pl-4 pr-1 text-left font-medium text-slate-500">#</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Student</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Role</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Verification</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-medium text-slate-500">Account</th>
                            <th scope="col" class="px-4 py-2.5 text-right font-medium text-slate-500"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $record)
                            <tr wire:key="v-{{ $record['user_id'] }}" class="transition-colors hover:bg-slate-50">
                                <td class="py-3 pl-4 pr-1 text-xs tabular-nums text-slate-500">{{ ($currentPage - 1) * $perPage + $loop->iteration }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#e2e9f7] text-xs font-semibold text-[#1E4090]" aria-hidden="true">{{ \App\Support\Honorifics::initials($record['name']) }}</span>
                                        <div class="min-w-0">
                                            <p class="max-w-[240px] truncate font-medium text-slate-900">{{ $record['name'] }}</p>
                                            <p class="mt-0.5 text-xs tabular-nums text-slate-500">{{ $record['student_number'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-slate-700">{{ $record['role'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $verificationBadge($record['verification_status']) }}">
                                        {{ ucfirst(str_replace('_', ' ', $record['verification_status'])) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex items-center gap-1.5 text-xs {{ $record['account_status'] === 'active' ? 'text-slate-700' : 'text-rose-700' }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $record['account_status'] === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>{{ ucfirst($record['account_status']) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="viewHistory({{ $record['user_id'] }})"
                                            class="inline-flex min-h-8 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                            History ({{ $record['history_count'] }})
                                        </button>
                                        <button wire:click="toggleAccountStatus({{ $record['user_id'] }})"
                                            onclick="return confirm('{{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }} this account?')"
                                            class="inline-flex min-h-8 items-center rounded-lg px-3 text-xs font-medium transition-colors {{ $record['account_status'] === 'active' ? 'text-rose-700 hover:bg-rose-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                                            {{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-slate-500">No students match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Cards on narrow screens, as on Workflow review --}}
        <ul class="grid gap-3 cq-md:hidden" aria-label="Students">
            @forelse($records as $record)
                <li wire:key="vm-{{ $record['user_id'] }}" class="rounded-2xl border border-slate-200 bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-[15px] font-semibold text-slate-900">{{ $record['name'] }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $record['student_number'] }} · {{ $record['role'] }}</p>
                        </div>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $verificationBadge($record['verification_status']) }}">
                            {{ ucfirst(str_replace('_', ' ', $record['verification_status'])) }}
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3">
                        <span class="mr-auto inline-flex items-center gap-1.5 text-xs {{ $record['account_status'] === 'active' ? 'text-slate-600' : 'text-rose-700' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ $record['account_status'] === 'active' ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>{{ ucfirst($record['account_status']) }}
                        </span>
                        <button wire:click="viewHistory({{ $record['user_id'] }})"
                            class="inline-flex min-h-11 items-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700">History ({{ $record['history_count'] }})</button>
                        <button wire:click="toggleAccountStatus({{ $record['user_id'] }})"
                            onclick="return confirm('{{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }} this account?')"
                            class="inline-flex min-h-11 items-center rounded-lg px-3 text-xs font-medium {{ $record['account_status'] === 'active' ? 'text-rose-700 hover:bg-rose-50' : 'text-emerald-700 hover:bg-emerald-50' }}">
                            {{ $record['account_status'] === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                    </div>
                </li>
            @empty
                <li class="rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center text-sm text-slate-500">No students match these filters.</li>
            @endforelse
        </ul>

        @if($totalPages > 1)
            <div class="flex items-center justify-between gap-2">
                <button wire:click="$set('currentPage', {{ max(1, $currentPage - 1) }})" @disabled($currentPage === 1)
                    class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:text-slate-300 disabled:hover:bg-white">Previous</button>
                <span class="text-sm text-slate-500">Page {{ $currentPage }} of {{ $totalPages }}</span>
                <button wire:click="$set('currentPage', {{ min($totalPages, $currentPage + 1) }})" @disabled($currentPage === $totalPages)
                    class="inline-flex min-h-9 items-center rounded-lg border border-slate-200 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:text-slate-300 disabled:hover:bg-white">Next</button>
            </div>
        @endif
    </section>

    {{-- History: teleported out of the layout so it covers the whole screen --}}
    @if($showHistoryModal)
        @teleport('body')
        <div class="fixed inset-0 z-[9999] flex items-end justify-center bg-slate-900/50 backdrop-blur-sm sm:items-center sm:p-4"
             wire:click.self="closeHistory" role="dialog" aria-modal="true" aria-labelledby="history-title">
            <div class="max-h-[88dvh] w-full max-w-2xl overflow-y-auto rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h3 id="history-title" class="text-lg font-semibold text-slate-900">Verification history</h3>
                        @if ($historyUser)
                            <p class="text-sm text-slate-500">{{ $historyUser->full_name }}</p>
                        @endif
                    </div>
                    <button wire:click="closeHistory" aria-label="Close" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100">
                        <i class='bx bx-x text-2xl' aria-hidden="true"></i>
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse($historyRecords as $entry)
                        <div class="rounded-xl bg-slate-50 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $verificationBadge($entry->status) }}">{{ ucfirst($entry->status) }}</span>
                                    <p class="mt-1.5 text-sm text-slate-600">{{ $entry->semester }} semester · AY {{ $entry->academic_year }}</p>
                                </div>
                                <p class="text-xs text-slate-500">{{ $entry->created_at?->format('M j, Y g:i A') }}</p>
                            </div>
                            @if($entry->e_slip_path)
                                <a href="{{ asset('storage/' . $entry->e_slip_path) }}" target="_blank" rel="noopener"
                                   class="mt-2 inline-block text-sm font-semibold text-[#2A57B4] hover:underline">View e-slip</a>
                            @endif
                            @if(!empty($entry->ocr_data))
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-sm font-medium text-slate-600">Scanned details</summary>
                                    <pre class="mt-1 overflow-x-auto rounded-lg bg-white p-2 text-xs">{{ json_encode($entry->ocr_data, JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            @endif
                        </div>
                    @empty
                        <p class="py-8 text-center text-sm text-slate-500">No submissions on file.</p>
                    @endforelse
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
