{{-- AI summary of the student's own activity (Gemini, the e-slip verification's key and model). --}}
@php
    $result = $result ?? null;
    $error = $error ?? null;
@endphp
<section class="relative overflow-hidden rounded-2xl border border-[#2A57B4]/15 bg-gradient-to-br from-[#2A57B4]/[0.06] via-white to-white p-5 sm:p-6"
         aria-labelledby="ai-summary-title" aria-busy="{{ $loading ? 'true' : 'false' }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#2A57B4] text-white" aria-hidden="true"><i class='bx bxs-magic-wand text-base'></i></span>
            <div>
                <h2 id="ai-summary-title" class="text-base font-semibold text-gray-900">AI summary</h2>
                <p class="text-xs text-gray-500">Written by AI from your activity below. Check the details before acting on it.</p>
            </div>
        </div>
        @unless ($loading)
            <button type="button" wire:click="refresh" wire:loading.attr="disabled" wire:target="refresh"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:opacity-60 [@media(pointer:coarse)]:min-h-[44px]">
                <i class='bx bx-refresh text-base' wire:loading.class="bx-spin" wire:target="refresh" aria-hidden="true"></i>
                <span wire:loading.remove wire:target="refresh">Refresh</span>
                <span wire:loading wire:target="refresh">Writing…</span>
            </button>
        @endunless
    </div>

    @if ($loading)
        <div class="mt-5 space-y-2.5" aria-hidden="true">
            <div class="h-5 w-2/3 animate-pulse rounded bg-gray-200/80"></div>
            <div class="h-3.5 w-full animate-pulse rounded bg-gray-100"></div>
            <div class="h-3.5 w-11/12 animate-pulse rounded bg-gray-100"></div>
            <div class="h-3.5 w-3/5 animate-pulse rounded bg-gray-100"></div>
        </div>
        <p class="sr-only">Writing your summary…</p>
    @elseif ($result)
        <div class="mt-5" wire:loading.class="opacity-50" wire:target="refresh">
            @if ($result['headline'])
                <p class="text-lg font-semibold leading-snug tracking-tight text-gray-900">{{ $result['headline'] }}</p>
            @endif
            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-700">{{ $result['summary'] }}</p>

            @if ($result['next_steps'])
                <h3 class="mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500">Suggested next steps</h3>
                <ul class="mt-2 space-y-1.5">
                    @foreach ($result['next_steps'] as $step)
                        <li class="flex items-start gap-2 text-sm text-gray-800">
                            <i class='bx bx-right-arrow-alt mt-0.5 text-base text-[#2A57B4]' aria-hidden="true"></i>{{ $step }}
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="mt-4 text-[11px] text-gray-400">
                Updated {{ \Carbon\Carbon::parse($result['generated_at'])->diffForHumans() }}
            </p>
        </div>
    @endif

    @if ($error)
        <p role="status" class="mt-4 rounded-lg bg-amber-50 px-3.5 py-2.5 text-sm text-amber-900">{{ $error }}</p>
    @endif
</section>
