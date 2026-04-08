<div class="space-y-6" wire:poll.5s>
    @php
        $currentStatusClasses = match ($currentStatus['status']) {
            'Cleared' => 'border-[#cfe9db] bg-[#f3fbf6] text-[#1f8a4c]',
            'Pending' => 'border-[#ffd9a1] bg-[#fff8ef] text-[#f59e0b]',
            'Uncleared' => 'border-[#efc8c1] bg-[#fdf3f1] text-[#b9432d]',
            default => 'border-slate-200 bg-white text-slate-700',
        };
    @endphp

    <section class="rounded-[28px] border border-[#d7e0ee] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Current Status</p>
                <div class="mt-4 rounded-[22px] border p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)] {{ $currentStatusClasses }}">
                    <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-700">Status</p>
                    <p class="mt-1 text-5xl font-extrabold leading-none">
                        {{ $currentStatus['status_word'] }}
                    </p>
                    <p class="mt-2 text-xs font-black uppercase tracking-[0.14em] text-slate-700">
                        {{ $currentStatus['period_label'] }}
                    </p>
                </div>
            </div>

            <div class="grid gap-3 text-sm text-slate-600 lg:min-w-[260px]">
                <div>
                    <p class="font-semibold text-slate-900">Student</p>
                    <p>{{ $fullName ?: ($user->first_name ?? 'Student') }}</p>
                    <p>{{ $user->student_number ?: 'No student number' }}</p>
                    <p>{{ $formattedYearLevel }}</p>
                </div>

                <div>
                    <p><span class="font-semibold text-slate-900">Tagged by:</span> {{ $currentStatus['tagged_by'] ?: '-' }}</p>
                    <p><span class="font-semibold text-slate-900">Remarks:</span> {{ $currentStatus['remarks'] ?: '-' }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-[28px] border border-[#d7e0ee] bg-white p-6 shadow-[0_18px_45px_rgba(15,23,42,0.06)]">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Clearance History</p>
                <h2 class="mt-2 text-2xl font-extrabold text-slate-900">Tagged Status Updates</h2>
                <p class="mt-2 text-sm text-slate-500">This page refreshes automatically so new admin status changes appear here within a few seconds.</p>
            </div>

            <div class="relative w-full md:w-[220px]">
                <select wire:model.live="historyStatusFilter"
                        class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-4 py-3 pr-10 text-sm font-semibold text-slate-700 outline-none transition focus:border-[#2A57B4] focus:ring-4 focus:ring-[#2A57B4]/10">
                    <option value="">All Statuses</option>
                    @foreach($statusOptions as $statusOption)
                        <option value="{{ $statusOption }}">{{ $statusOption }}</option>
                    @endforeach
                </select>
                <i class='bx bx-chevron-down pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-xl text-slate-400'></i>
            </div>
        </div>

        <div class="mt-6 space-y-4">
            @forelse($history as $entry)
                @php
                    $historyStatusClasses = match ($entry['status']) {
                        'Cleared' => 'border-[#cfe9db] bg-[#f3fbf6] text-[#1f8a4c]',
                        'Pending' => 'border-[#ffd9a1] bg-[#fff8ef] text-[#f59e0b]',
                        'Uncleared' => 'border-[#efc8c1] bg-[#fdf3f1] text-[#b9432d]',
                        default => 'border-slate-200 bg-white text-slate-700',
                    };
                @endphp
                <div class="rounded-[22px] border p-5 shadow-[0_10px_28px_rgba(15,23,42,0.05)] {{ $historyStatusClasses }}">
                    <div class="grid gap-6 lg:grid-cols-[1.35fr_0.95fr] lg:items-start">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-700">Status</p>
                            <p class="mt-1 text-5xl font-extrabold leading-none">
                                {{ $entry['status_word'] }}
                            </p>
                            <p class="mt-2 text-xs font-black uppercase tracking-[0.14em] text-slate-700">
                                {{ $entry['period_label'] }}
                            </p>
                        </div>

                        <div class="space-y-1 text-sm text-slate-700">
                            <p><span class="font-semibold text-slate-900">Tagged by:</span> {{ $entry['tagged_by'] }}</p>
                            <p><span class="font-semibold text-slate-900">Remarks:</span> {{ $entry['remarks'] }}</p>
                            <p><span class="font-semibold text-slate-900">Last Updated:</span> {{ $entry['last_updated'] }}</p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-[22px] border border-dashed border-slate-300 bg-slate-50 px-5 py-10 text-center text-sm text-slate-500">
                    No clearance history is available yet. Once an admin updates your status in Clearance Monitoring, the entry will appear here automatically.
                </div>
            @endforelse
        </div>
    </section>
</div>
