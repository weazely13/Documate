@php
    $theme = fn (?string $status) => match ($status) {
        'Cleared' => ['c1' => '#064e3b', 'c2' => '#047857', 'c3' => '#10b981', 'tint' => '#d9f3e1', 'ink' => '#047857', 'deep' => '#065f46', 'icon' => 'bx-check-shield',
                      'means' => 'Nothing is owed for this semester. You are cleared.'],
        'Pending' => ['c1' => '#78350f', 'c2' => '#b45309', 'c3' => '#f59e0b', 'tint' => '#fef3c7', 'ink' => '#b45309', 'deep' => '#78350f', 'icon' => 'bx-time-five',
                      'means' => 'Your organization is still checking your records. No action is needed unless the remarks say so.'],
        'Uncleared' => ['c1' => '#881337', 'c2' => '#be123c', 'c3' => '#f43f5e', 'tint' => '#fde0ec', 'ink' => '#be123c', 'deep' => '#9f1239', 'icon' => 'bx-error-circle',
                        'means' => 'Something is still due. Settle what the remarks list, then ask your organization to update your status.'],
        default => ['c1' => '#0d253d', 'c2' => '#1e3f6b', 'c3' => '#475d7a', 'tint' => '#ecebe7', 'ink' => '#66707f', 'deep' => '#273951', 'icon' => 'bx-help-circle',
                    'means' => 'Your organization has not tagged your clearance for this semester yet.'],
    };
    $now = $theme($currentStatus['status']);
    [$periodYear, $periodTerm] = array_pad(array_map('trim', explode('|', (string) $currentStatus['period_label'], 2)), 2, null);
    $currentEntry = [
        'status' => $currentStatus['status'],
        'semester' => $periodTerm ?? '',
        'academic_year' => $periodTerm ? $periodYear : '',
        'tagged_by' => $currentStatus['tagged_by'],
        'tagged_at' => $history->firstWhere('is_current', true)['tagged_at'] ?? null,
    ];

    $rank = fn ($e) => [(int) substr((string) $e['academic_year'], 0, 4), str_contains(strtolower($e['semester']), 'second') ? 2 : (str_contains(strtolower($e['semester']), 'summer') || str_contains(strtolower($e['semester']), 'mid') ? 3 : 1)];
    $timeline = $history->sortBy($rank)->values();
    $counts = collect(['Cleared', 'Pending', 'Uncleared'])->mapWithKeys(fn ($s) => [$s => $timeline->where('status', $s)->count()]);
    $byYear = $history->groupBy('academic_year');
@endphp

<div class="mx-auto max-w-5xl space-y-6 sm:space-y-8" wire:poll.15s>

    {{-- ============ THIS SEMESTER ============ --}}
    <section aria-label="This semester's clearance">
        @include('livewire.student.partials.clearance-card', ['t' => $now, 'e' => $currentEntry + ['remarks' => $currentStatus['remarks'], 'is_current' => true], 'hero' => true,
            'holder' => $fullName ?: ($user->first_name ?? 'Student'), 'number' => $user->student_number, 'means' => $now['means']])
    </section>

    {{-- ============ RECORD AT A GLANCE ============ --}}
    @if ($timeline->isNotEmpty())
        <section class="rounded-xl border border-[#e7e5e1] bg-white p-5 sm:p-6">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:justify-between">
                <h2 class="text-[15px] font-semibold text-[#0d253d]">Your clearance record</h2>
                <p class="text-[13px] text-[#66707f]"><span class="font-semibold tabular-nums text-[#047857]">{{ $counts['Cleared'] }}</span> of <span class="tabular-nums">{{ $timeline->count() }}</span> semesters cleared</p>
            </div>
            <ol class="mt-4 flex items-start gap-1 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($timeline as $e)
                    @php $t = $theme($e['status']); @endphp
                    <li class="flex min-w-[4.5rem] flex-1 flex-col items-center text-center" title="{{ $e['period_label'] }}: {{ $e['status'] }}">
                        <div class="flex w-full items-center">
                            <span class="h-px flex-1 {{ $loop->first ? 'bg-transparent' : 'bg-[#e7e5e1]' }}"></span>
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full text-white {{ $e['is_current'] ? 'ring-2 ring-offset-2' : '' }}" style="background: linear-gradient(135deg, {{ $t['c1'] }}, {{ $t['c3'] }}); --tw-ring-color: {{ $t['ink'] }};">
                                <i class="bx {{ $t['icon'] }} text-base"></i>
                            </span>
                            <span class="h-px flex-1 {{ $loop->last ? 'bg-transparent' : 'bg-[#e7e5e1]' }}"></span>
                        </div>
                        <span class="mt-1.5 text-[11.5px] font-medium leading-tight text-[#273951]">{{ trim(str_ireplace('semester', '', $e['semester'])) }}{{ str_contains(strtolower($e['semester']), 'summer') ? '' : ' sem' }}</span>
                        <span class="text-[10.5px] tabular-nums text-[#9a978f]">{{ $e['academic_year'] }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- ============ BY SEMESTER ============ --}}
    <section>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold tracking-[-0.015em] text-[#0d253d]">By semester</h2>
                <p class="mt-0.5 text-[13px] text-[#66707f]">Updates here as soon as your organization tags you.</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex gap-1 overflow-x-auto rounded-lg bg-[#f5f4f2] p-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden" role="group" aria-label="Filter by status">
                    @foreach (['' => ['All', $timeline->count()], 'Cleared' => ['Cleared', $counts['Cleared']], 'Pending' => ['Pending', $counts['Pending']], 'Uncleared' => ['Uncleared', $counts['Uncleared']]] as $value => [$label, $n])
                        <button type="button" wire:click="$set('historyStatusFilter', '{{ $value }}')" aria-pressed="{{ $historyStatusFilter === $value ? 'true' : 'false' }}"
                                class="shrink-0 whitespace-nowrap rounded-md px-2.5 py-1 text-[13px] font-medium transition {{ $historyStatusFilter === $value ? 'bg-white text-[#0d253d] shadow-sm' : 'text-[#66707f] hover:text-[#0d253d]' }}">
                            {{ $label }} <span class="tabular-nums text-[#9a978f]">{{ $n }}</span>
                        </button>
                    @endforeach
                </div>
                <button type="button" wire:click="$set('sortBy', '{{ $sortBy === 'oldest' ? 'latest' : 'oldest' }}')"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-[#e7e5e1] bg-white text-[#4a5568] hover:bg-[#f5f4f2]"
                        title="{{ $sortBy === 'oldest' ? 'Oldest first' : 'Newest first' }}" aria-label="Sort {{ $sortBy === 'oldest' ? 'newest first' : 'oldest first' }}">
                    <i class="bx {{ $sortBy === 'oldest' ? 'bx-sort-up' : 'bx-sort-down' }} text-lg"></i>
                </button>
            </div>
        </div>

        @forelse ($byYear as $year => $entries)
            <div class="mt-6 sm:mt-7">
                <div class="mb-3 flex items-center gap-3">
                    <h3 class="text-sm font-semibold tabular-nums text-[#273951]">A.Y. {{ $year }}</h3>
                    <span class="h-px flex-1 bg-[#e7e5e1]"></span>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($entries as $e)
                        @php $t = $theme($e['status']); @endphp
                        <article wire:key="sem-{{ $e['semester_id'] ?? $loop->parent->index . '-' . $loop->index }}" class="transition duration-300 hover:-translate-y-1">
                            @include('livewire.student.partials.clearance-card', ['t' => $t, 'e' => $e, 'hero' => false])
                        </article>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="mt-6 flex flex-col items-center rounded-xl border border-dashed border-[#d4d1cb] bg-white px-6 py-14 text-center">
                <span class="grid h-12 w-12 place-items-center rounded-xl bg-[#f5f4f2] text-[#9a978f]"><i class="bx bx-credit-card-front text-2xl"></i></span>
                @if ($historyStatusFilter !== '')
                    <p class="mt-3 font-medium text-[#0d253d]">No {{ strtolower($historyStatusFilter) }} semesters</p>
                    <button type="button" wire:click="$set('historyStatusFilter', '')" class="mt-2 text-sm font-medium text-[#2a57b4] hover:underline">Show all semesters</button>
                @else
                    <p class="mt-3 font-medium text-[#0d253d]">No clearance records yet</p>
                    <p class="mt-1 max-w-sm text-sm text-[#66707f]">When your organization tags your clearance, each semester appears here.</p>
                @endif
            </div>
        @endforelse
    </section>
</div>
