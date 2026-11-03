{{-- The AI queue, in the top bar beside the notifications: an icon with a live badge, and a dropdown of every
     background AI check (document uploads, workflow review, appointment review). Running checks sit on top;
     finished ones follow, newest first. A new upload ("scan-queued") opens it. Kinds keep the dashboard's
     folder tints: uploads sky, workflow lavender, appointments yellow. --}}
@php
    use App\Livewire\Admin\ScanStatusBar;

    $kinds = [
        'upload' => ['bx-scan', 'bg-[#dcecfa] text-[#1E4090]', 'Upload'],
        'workflow' => ['bx-git-branch', 'bg-[#e6e0f5] text-violet-800', 'Workflow'],
        'appointment' => ['bx-calendar-event', 'bg-[#fef3c7] text-amber-800', 'Appointment'],
    ];
    $tones = [
        'run' => 'text-[#1E4090]',
        'done' => 'text-emerald-700',
        'warn' => 'text-amber-800',
        'bad' => 'text-rose-700',
        'idle' => 'text-slate-500',
    ];
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
@endphp
<div x-data="{ open: $wire.entangle('expanded').live }" class="relative" wire:poll.3s @keydown.escape.window="open = false">
    <button type="button" x-ref="trigger" @click="open = ! open" :aria-expanded="open" aria-haspopup="dialog"
        aria-label="AI queue{{ $this->activeCount ? ', ' . $this->activeCount . ' running' : '' }}" data-tooltip="AI queue"
        class="relative rounded-full p-2 transition hover:bg-slate-100 {{ $focus }}" :class="open && 'bg-[#e2e9f7]'">
        <i class='bx bx-scan text-xl text-slate-600' :class="open && '!text-[#2A57B4]'" aria-hidden="true"></i>
        @if ($hasError)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[10px] font-semibold text-white ring-2 ring-white" aria-hidden="true">!</span>
        @elseif ($this->activeCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[#2A57B4] px-1 text-[10px] font-semibold tabular-nums text-white ring-2 ring-white" aria-hidden="true">
                {{ $this->activeCount > 9 ? '9+' : $this->activeCount }}
            </span>
            <span class="absolute -right-0.5 -top-0.5 h-4 w-4 animate-ping rounded-full bg-[#2A57B4]/40 motion-reduce:hidden" aria-hidden="true"></span>
        @endif
    </button>

    {{-- Below sm the panel spans the screen under the top bar; from sm up it hangs from the icon --}}
    <div x-show="open" x-cloak @click.outside="open = false" role="dialog" aria-label="AI queue"
        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-x-3 top-[4.25rem] z-50 flex max-h-[calc(100dvh-5.5rem)] flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-[26rem] sm:max-h-[min(36rem,calc(100dvh-5.5rem))]">

        @if ($expanded)
            @php
                $all = $this->items;
                $q = $this->queue;
                $shown = $kind ? $all->where('kind', $kind) : $all;
                $running = $shown->where('running', true);
                $recent = $shown->where('running', false)->take(25);
            @endphp

            {{-- Header --}}
            <div class="shrink-0 border-b border-slate-200 px-4 pb-3 pt-3.5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[15px] font-semibold text-slate-900">AI queue</p>
                        <p class="mt-0.5 text-xs text-slate-500" aria-live="polite">
                            @if ($this->activeCount)
                                <span class="inline-flex items-center gap-1 font-medium text-[#1E4090]"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-[#2A57B4] motion-reduce:animate-none" aria-hidden="true"></span>{{ $this->activeCount }} running</span>
                                @if ($q['waiting']) · {{ $q['waiting'] }} waiting their turn @endif
                            @else
                                Nothing running. Checks run one at a time, in order.
                            @endif
                        </p>
                    </div>
                    <button type="button" wire:click="refreshNow" title="Refresh now" aria-label="Refresh now"
                        class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-[#2A57B4] {{ $focus }}">
                        <i class='bx bx-refresh text-lg' wire:loading.class="animate-spin" wire:target="refreshNow" aria-hidden="true"></i>
                    </button>
                </div>

                {{-- Kind filter --}}
                <div class="mt-3 flex gap-1 rounded-lg bg-slate-100 p-1" role="group" aria-label="Show">
                    @foreach (['' => 'All'] + ScanStatusBar::KINDS as $k => $label)
                        @php $n = ($k ? $all->where('kind', $k) : $all)->where('running', true)->count(); @endphp
                        <button type="button" wire:click="setKind('{{ $k }}')" aria-pressed="{{ $kind === $k ? 'true' : 'false' }}"
                                class="inline-flex min-h-7 flex-1 items-center justify-center gap-1 rounded-md px-1.5 text-xs transition-colors {{ $focus }}
                                    {{ $kind === $k ? 'bg-white font-medium text-[#24499A] shadow-sm' : 'text-slate-600 hover:text-slate-900' }}">
                            {{ $label }}
                            @if ($n)<span class="rounded-full bg-[#2A57B4] px-1.5 text-[10px] font-semibold tabular-nums text-white">{{ $n }}</span>@endif
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="thin-scroll min-h-0 flex-1 overflow-y-auto">
                @if ($hasError)
                    <p class="flex items-center gap-2 border-b border-rose-100 bg-rose-50 px-4 py-2.5 text-xs text-rose-800" role="alert">
                        <i class='bx bx-error-circle' aria-hidden="true"></i> Couldn't load the latest status. Press refresh to try again.
                    </p>
                @endif

                @foreach (['Running now' => $running, 'Recent' => $recent] as $heading => $list)
                    @if ($list->isNotEmpty())
                        <p class="sticky top-0 z-10 border-b border-slate-100 bg-slate-50/95 px-4 py-1.5 text-xs font-medium text-slate-500 backdrop-blur-sm">{{ $heading }}</p>
                        <ul>
                            @foreach ($list as $i)
                                @php [$icon, $tint, $kindLabel] = $kinds[$i['kind']]; @endphp
                                <li wire:key="aiq-{{ $i['key'] }}">
                                    <a href="{{ $i['url'] }}" wire:navigate @click="open = false"
                                       class="flex items-start gap-3 border-b border-slate-100 px-4 py-2.5 transition-colors hover:bg-slate-50 {{ $focus }}">
                                        <span class="relative mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg {{ $tint }}" title="{{ $kindLabel }}">
                                            <i class='bx {{ $icon }} text-base' aria-hidden="true"></i>
                                            @if ($i['running'])
                                                <span class="absolute -bottom-0.5 -right-0.5 grid h-3.5 w-3.5 place-items-center rounded-full bg-white ring-1 ring-slate-200" aria-hidden="true">
                                                    <i class="bx bx-loader-alt animate-spin text-[10px] text-[#2A57B4] motion-reduce:animate-none"></i>
                                                </span>
                                            @endif
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-baseline justify-between gap-2">
                                                <span class="truncate text-sm font-medium text-slate-900">{{ $i['who'] }}</span>
                                                <span class="shrink-0 text-[11px] tabular-nums text-slate-500">{{ $i['at']?->diffForHumans(null, true, true) }}</span>
                                            </span>
                                            <span class="block truncate text-xs text-slate-500"><span class="sr-only">{{ $kindLabel }}: </span>{{ $i['what'] }}</span>
                                            <span class="mt-0.5 block truncate text-xs font-medium {{ $tones[$i['tone']] }}">{{ $i['status'] }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach

                @if ($running->isEmpty() && $recent->isEmpty())
                    <div class="px-6 py-12 text-center">
                        <i class="bx bx-check-shield mb-2 !block text-3xl text-slate-300" aria-hidden="true"></i>
                        <p class="text-sm font-medium text-slate-700">No AI checks {{ $kind ? 'for ' . strtolower(ScanStatusBar::KINDS[$kind]) : '' }} lately</p>
                        <p class="mt-0.5 text-xs text-slate-500">Uploads, workflow pre-checks and appointment checks appear here while they run.</p>
                    </div>
                @endif
            </div>

        @else
            <p class="px-4 py-10 text-center text-sm text-slate-500"><i class="bx bx-loader-alt animate-spin" aria-hidden="true"></i> Loading…</p>
        @endif
    </div>
</div>
