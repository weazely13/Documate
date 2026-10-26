@php
    $tones = \App\Support\DocumentProgress::TONES;

    $attentionStyle = [
        'amber' => ['wrap' => 'bg-amber-50', 'icon' => 'bx-edit bg-amber-100 text-amber-700', 'title' => 'text-amber-950'],
        'rose' => ['wrap' => 'bg-rose-50', 'icon' => 'bx-error-circle bg-rose-100 text-rose-700', 'title' => 'text-rose-950'],
        'blue' => ['wrap' => 'bg-blue-50', 'icon' => 'bx-bell bg-blue-100 text-[#2A57B4]', 'title' => 'text-slate-900'],
        'slate' => ['wrap' => 'bg-slate-50', 'icon' => 'bx-user bg-slate-200 text-slate-600', 'title' => 'text-slate-900'],
    ];
    $shown = array_slice($attention, 0, 3);
    $more = count($attention) - count($shown);

    $clearanceStyle = match ($clearance['status'] ?? '') {
        'Cleared' => ['card' => 'bg-emerald-600 text-white', 'icon' => 'bx-check-shield', 'note' => 'You are cleared for this semester.'],
        'Pending' => ['card' => 'bg-amber-500 text-white', 'icon' => 'bx-time-five', 'note' => 'Some offices have not cleared you yet.'],
        'Uncleared' => ['card' => 'bg-rose-600 text-white', 'icon' => 'bx-x-circle', 'note' => 'An office has a hold on your clearance.'],
        default => ['card' => 'bg-slate-700 text-white', 'icon' => 'bx-help-circle', 'note' => 'Not tagged yet for this semester.'],
    };
@endphp

<div class="relative z-0 mx-auto max-w-6xl space-y-6 pb-12" wire:poll.60s>

    @include('livewire.partials.verify-account-card')

    {{-- Greeting + clearance --}}
    <section class="grid gap-6 lg:grid-cols-3">
        @include('livewire.partials.greeting-card', [
            'name' => $firstName,
            'subtitle' => 'Your documents, appointments and clearance in one place.',
            'facts' => $facts,
            'class' => 'lg:col-span-2',
        ])

        <a href="{{ route('student.clearance-status') }}" wire:navigate
           class="flex flex-col rounded-2xl p-6 shadow-sm transition hover:brightness-105 {{ $clearanceStyle['card'] }}">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-medium opacity-90">Clearance</p>
                <i class='bx {{ $clearanceStyle['icon'] }} text-3xl opacity-90' aria-hidden="true"></i>
            </div>
            <p class="mt-4 text-3xl font-bold tracking-tight">{{ $clearance['status_word'] ?? 'Not tagged' }}</p>
            <p class="mt-1 text-sm opacity-90">{{ $clearanceStyle['note'] }}</p>
            <p class="mt-auto pt-6 text-xs opacity-80">{{ $clearance['period_label'] ?? '' }} · View details &rarr;</p>
        </a>
    </section>

    {{-- What needs you + next appointment --}}
    <section class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/70 bg-white p-5 lg:col-span-2">
            <h2 class="text-base font-semibold text-slate-900">Needs your attention</h2>

            @if ($shown)
                <div class="mt-4 space-y-2.5">
                    @foreach ($shown as $i => $item)
                        <a href="{{ $item['url'] }}" wire:navigate wire:key="attn-{{ $i }}"
                           class="flex items-center gap-3 rounded-xl px-3.5 py-3 transition hover:brightness-[0.98] {{ $attentionStyle[$item['tone']]['wrap'] }}">
                            <i class='bx {{ $attentionStyle[$item['tone']]['icon'] }} flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-lg' aria-hidden="true"></i>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold {{ $attentionStyle[$item['tone']]['title'] }}">{{ $item['title'] }}</span>
                                @if ($item['text'])
                                    <span class="mt-0.5 block truncate text-sm text-slate-600">{{ $item['text'] }}</span>
                                @endif
                            </span>
                            <i class='bx bx-chevron-right shrink-0 text-xl text-slate-400' aria-hidden="true"></i>
                        </a>
                    @endforeach
                </div>
                @if ($more > 0)
                    <a href="{{ route('student.documents.index') }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090]">
                        {{ $more }} more {{ \Illuminate\Support\Str::plural('thing', $more) }} to check &rarr;
                    </a>
                @endif
            @else
                <div class="mt-4 flex items-center gap-3 rounded-xl bg-emerald-50 px-4 py-4">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"><i class='bx bx-party text-lg' aria-hidden="true"></i></span>
                    <p class="text-sm text-emerald-900"><span class="font-semibold">You're all caught up.</span> Nothing needs you right now.</p>
                </div>
            @endif
        </div>

        <a href="{{ route('student.appointments.index') }}" wire:navigate
           class="flex flex-col rounded-2xl border border-slate-200/70 bg-white p-5 transition hover:border-[#2A57B4]/30">
            <h2 class="text-base font-semibold text-slate-900">Next appointment</h2>
            @if ($appointment)
                <div class="mt-4 flex items-center gap-4">
                    <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-xl bg-[#2A57B4] text-white">
                        <span class="text-[11px] font-semibold uppercase opacity-80">{{ $appointment['date']->format('M') }}</span>
                        <span class="text-2xl font-bold leading-none">{{ $appointment['date']->format('d') }}</span>
                    </div>
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">
                            {{ $appointment['is_today'] ? 'Today' : ($appointment['date']->isTomorrow() ? 'Tomorrow' : $appointment['date']->format('l')) }},
                            {{ strtolower($appointment['session']) }}
                        </p>
                        <p class="text-sm text-slate-600">
                            @if ($appointment['status'] === 'pending')
                                Waiting for approval
                            @elseif ($appointment['queue_number'])
                                Your number is <span class="font-semibold text-slate-900">{{ $appointment['queue_number'] }}</span>
                            @else
                                Approved
                            @endif
                        </p>
                    </div>
                </div>
                @if ($appointment['now_serving'])
                    <p class="mt-4 rounded-lg bg-blue-50 px-3 py-2 text-sm text-[#1E4090]">Now serving <span class="font-bold">{{ $appointment['now_serving'] }}</span></p>
                @endif
            @else
                <div class="mt-4 flex flex-1 flex-col items-center justify-center rounded-xl bg-slate-50 px-4 py-6 text-center">
                    <i class='bx bx-calendar text-3xl text-slate-300' aria-hidden="true"></i>
                    <p class="mt-2 text-sm text-slate-600">No upcoming appointment</p>
                </div>
            @endif
        </a>
    </section>

    {{-- Documents in progress --}}
    <section>
        <div class="mb-3 flex items-baseline justify-between gap-3">
            <h2 class="text-base font-semibold text-slate-900">In progress <span class="font-normal text-slate-400">{{ $activeCount }}</span></h2>
            <div class="flex items-center gap-4 text-sm font-semibold">
                <a href="{{ route('student.documents.index') }}" wire:navigate class="text-slate-500 hover:text-slate-800">All documents</a>
                <a href="{{ route('student.new-transaction') }}" wire:navigate class="text-[#2A57B4] hover:text-[#1E4090]">+ New</a>
            </div>
        </div>

        @if ($active->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($active as $doc)
                    <a href="{{ $doc['href'] }}" wire:navigate wire:key="active-{{ $doc['id'] }}"
                       class="flex flex-col rounded-2xl border border-slate-200/70 bg-white p-5 transition hover:border-[#2A57B4]/30 hover:shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-[#2A57B4]">
                                <i class='bx bx-file text-xl' aria-hidden="true"></i>
                            </span>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $tones[$doc['tone']] }}">{{ $doc['label'] }}</span>
                        </div>
                        <p class="mt-3 text-sm font-semibold text-slate-900">{{ $doc['title'] }}</p>
                        <div class="mt-3 grid grid-cols-4 gap-1" aria-label="Step {{ $doc['step'] }} of 4">
                            @for ($i = 1; $i <= 4; $i++)
                                <span class="h-1.5 rounded-full {{ $i <= $doc['step'] ? 'bg-emerald-500' : ($i === $doc['step'] + 1 ? ($doc['tone'] === 'you' ? 'bg-amber-400' : 'bg-[#2A57B4]') : 'bg-slate-200') }}"></span>
                            @endfor
                        </div>
                        @if ($doc['next'])
                            <p class="mt-3 line-clamp-2 text-sm {{ $doc['tone'] === 'you' ? 'text-amber-800' : 'text-slate-600' }}">{{ $doc['next'] }}</p>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <a href="{{ route('student.new-transaction') }}" wire:navigate
               class="flex items-center gap-4 rounded-2xl border border-dashed border-slate-300 bg-white p-5 transition hover:border-[#2A57B4]/40">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-[#2A57B4]"><i class='bx bx-plus text-xl' aria-hidden="true"></i></span>
                <span>
                    <span class="block text-sm font-semibold text-slate-900">Nothing in progress</span>
                    <span class="block text-sm text-slate-500">Start a new transaction when you need a form.</span>
                </span>
            </a>
        @endif
    </section>
</div>
