{{-- Time-of-day greeting card (morning / afternoon / evening scenery).
     @include('livewire.partials.greeting-card', ['name' => 'Maria', 'subtitle' => '...', 'facts' => ['2026-2027 First Semester'], 'class' => 'lg:col-span-2'])
     'compact' => true gives a short banner (no subtitle, facts beside the greeting) for pages whose numbers should lead. --}}
@php
    $facts = $facts ?? [];
    $compact = $compact ?? false;
    $height = $compact ? 'min-h-[136px]' : 'min-h-[200px] sm:min-h-[220px]';
    $far = $compact ? 'h-16' : 'h-28 sm:h-32';
    $near = $compact ? 'h-11' : 'h-20 sm:h-24';
    $orb = $compact ? 'hidden h-10 w-10 sm:block' : 'h-16 w-16 sm:h-20 sm:w-20';
    $moon = $compact ? 'hidden h-9 w-9 sm:block' : 'h-14 w-14 sm:h-16 sm:w-16';
    // Sun and moon sit in open sky: mid-banner when compact, where the facts and greeting leave room.
    $sky = $compact ? 'left-[48%] top-[16%]' : 'right-[18%] top-[15%]';
@endphp

<div
    x-data="{
        hour: new Date().getHours(),
        get period() {
            if (this.hour >= 5 && this.hour < 12) return 'morning';
            if (this.hour >= 12 && this.hour < 18) return 'afternoon';
            return 'evening';
        },
        get greeting() {
            return { morning: 'Good morning', afternoon: 'Good afternoon', evening: 'Good evening' }[this.period];
        }
    }"
    x-init="setInterval(() => { hour = new Date().getHours() }, 60000)"
    class="relative isolate {{ $height }} overflow-hidden rounded-2xl border border-slate-200 shadow-sm {{ $class ?? '' }}"
>
    {{-- MORNING --}}
    <div x-show="period === 'morning'" x-transition.opacity class="absolute inset-0" aria-hidden="true">
        <div class="absolute inset-0 bg-gradient-to-b from-sky-100 via-blue-50 to-amber-50"></div>
        <div class="absolute bottom-[24%] left-[20%] {{ $orb }} rounded-full bg-amber-300 shadow-[0_0_60px_rgba(251,191,36,0.55)]"></div>
        <div class="absolute bottom-0 left-0 {{ $far }} w-full bg-slate-400/70"
             style="clip-path: polygon(0 100%, 0 65%, 15% 35%, 28% 70%, 43% 25%, 60% 65%, 76% 38%, 90% 68%, 100% 45%, 100% 100%);"></div>
        <div class="absolute bottom-0 left-0 {{ $near }} w-full bg-slate-600/80"
             style="clip-path: polygon(0 100%, 0 60%, 20% 30%, 35% 68%, 50% 20%, 66% 70%, 82% 40%, 100% 62%, 100% 100%);"></div>
    </div>

    {{-- AFTERNOON --}}
    <div x-show="period === 'afternoon'" x-transition.opacity class="absolute inset-0" aria-hidden="true">
        <div class="absolute inset-0 bg-gradient-to-b from-sky-300 via-sky-100 to-blue-50"></div>
        <div class="absolute {{ $sky }} {{ $orb }} rounded-full bg-yellow-300 shadow-[0_0_70px_rgba(253,224,71,0.65)]"></div>
        <div class="absolute left-[12%] top-[20%] opacity-70">
            <div class="h-5 w-20 rounded-full bg-white"></div>
            <div class="-mt-5 ml-5 h-7 w-12 rounded-full bg-white"></div>
        </div>
        <div class="absolute bottom-0 left-0 {{ $far }} w-full bg-slate-500/75"
             style="clip-path: polygon(0 100%, 0 62%, 15% 38%, 29% 68%, 44% 25%, 60% 65%, 76% 38%, 91% 67%, 100% 46%, 100% 100%);"></div>
        <div class="absolute bottom-0 left-0 {{ $near }} w-full bg-slate-700/80"
             style="clip-path: polygon(0 100%, 0 64%, 20% 32%, 36% 70%, 51% 22%, 67% 70%, 82% 42%, 100% 64%, 100% 100%);"></div>
    </div>

    {{-- EVENING --}}
    <div x-show="period === 'evening'" x-transition.opacity class="absolute inset-0" aria-hidden="true">
        <div class="absolute inset-0 bg-gradient-to-b from-indigo-950 via-indigo-900 to-slate-900"></div>
        <div class="absolute {{ $sky }} {{ $moon }} rounded-full bg-slate-100 shadow-[0_0_55px_rgba(255,255,255,0.35)]"></div>
        <div class="absolute left-[15%] top-[18%] h-1.5 w-1.5 rounded-full bg-white"></div>
        <div class="absolute left-[28%] top-[28%] h-1 w-1 rounded-full bg-white"></div>
        <div class="absolute left-[48%] top-[14%] h-1.5 w-1.5 rounded-full bg-white"></div>
        <div class="absolute right-[34%] top-[32%] h-1 w-1 rounded-full bg-white"></div>
        <div class="absolute bottom-0 left-0 {{ $far }} w-full bg-slate-800"
             style="clip-path: polygon(0 100%, 0 62%, 15% 38%, 29% 68%, 44% 25%, 60% 65%, 76% 38%, 91% 67%, 100% 46%, 100% 100%);"></div>
        <div class="absolute bottom-0 left-0 {{ $near }} w-full bg-slate-950"
             style="clip-path: polygon(0 100%, 0 64%, 20% 32%, 36% 70%, 51% 22%, 67% 70%, 82% 42%, 100% 64%, 100% 100%);"></div>
    </div>

    {{-- CONTENT --}}
    @if ($compact)
        <div class="relative flex flex-wrap items-start justify-between gap-x-6 gap-y-3 p-5 sm:px-6">
            <div>
                <p class="text-sm font-medium" :class="period === 'evening' ? 'text-indigo-200' : 'text-slate-600'">{{ now()->format('l, F j') }}</p>
                <h1 class="mt-0.5 text-2xl font-bold leading-tight tracking-tight"
                    :class="period === 'evening' ? 'text-white' : 'text-slate-900'">
                    <span x-text="greeting">Hello</span>, {{ $name }}!
                </h1>
            </div>
            @if ($facts)
                <div class="flex flex-wrap gap-2">
                    @foreach ($facts as $fact)
                        <span class="rounded-full px-3 py-1 text-xs font-medium backdrop-blur-sm"
                              :class="period === 'evening' ? 'bg-white/15 text-white' : 'bg-white/75 text-slate-700'">{{ $fact }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @else
        <div class="relative flex h-full min-h-[200px] flex-col p-6 sm:min-h-[220px] sm:p-8">
            <p class="text-sm font-medium" :class="period === 'evening' ? 'text-indigo-200' : 'text-slate-500'">{{ now()->format('l, F j') }}</p>
            <h1 class="mt-1 text-2xl font-bold leading-tight tracking-tight sm:text-3xl"
                :class="period === 'evening' ? 'text-white' : 'text-slate-900'">
                <span x-text="greeting">Hello</span>, {{ $name }}!
            </h1>
            @if (! empty($subtitle))
                <p class="mt-2 max-w-md text-sm leading-relaxed sm:text-base"
                   :class="period === 'evening' ? 'text-slate-200' : 'text-slate-600'">{{ $subtitle }}</p>
            @endif
            @if ($facts)
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($facts as $fact)
                        <span class="rounded-full px-3 py-1 text-xs font-medium backdrop-blur-sm"
                              :class="period === 'evening' ? 'bg-white/15 text-white' : 'bg-white/70 text-slate-700'">{{ $fact }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
