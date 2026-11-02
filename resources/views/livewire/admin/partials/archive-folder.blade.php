{{-- One folder of the transaction archive, drawn like the dashboard's: tab and back panel, the newest pages
     peeking out (real previews), the front holding the figures.
     Needs: $f (type, count, paperless, latest, thumbs, students?), $click (the wire:click call), $folderTheme, $focus. --}}
@php $th = $folderTheme($f['type']); @endphp
                            <li wire:key="folder-{{ md5($f['type']) }}" class="min-w-0">
                                {{-- The same folder as the dashboard: tab and back panel, pages peeking out, the front holding the figures.
                                     The pages are the newest documents inside, shrunk. --}}
                                <button type="button" wire:click="{{ $click }}"
                                        class="group relative isolate flex h-full w-full min-w-0 flex-col rounded-xl pt-3 text-left transition hover:-translate-y-0.5 motion-reduce:hover:translate-y-0 {{ $focus }}">
                                    <span class="absolute left-0 top-0 h-7 w-2/5 rounded-t-xl {{ $th['back'] }}" aria-hidden="true"></span>
                                    <span class="absolute inset-x-0 bottom-0 top-3 rounded-xl rounded-tl-none {{ $th['back'] }} shadow-md" aria-hidden="true"></span>

                                    @foreach ([1 => 'left-8 right-3 top-7 rotate-2 group-hover:-translate-y-1', 0 => 'left-3 right-7 top-5 -rotate-2 group-hover:-translate-y-2.5'] as $pi => $pos)
                                        @php $t = $f['thumbs'][$pi] ?? null; @endphp
                                        <span class="absolute {{ $pos }} h-32 overflow-hidden rounded-md bg-white shadow-sm ring-1 ring-black/5 transition duration-300" aria-hidden="true"
                                              @if ($t && ! $t['image']) x-data="{ s: 0.2, src: '' }" x-init="const fit = () => s = $el.clientWidth / 842; fit(); new ResizeObserver(fit).observe($el)" x-intersect.once.margin.200px="src = @js($t['src'])" @endif>
                                            @if ($t && $t['image'])
                                                <img src="{{ $t['src'] }}" alt="" loading="lazy" class="h-full w-full object-cover object-top">
                                            @elseif ($t)
                                                {{-- Ruled lines until the page has loaded --}}
                                                <span class="absolute left-3 right-8 top-3 h-1 rounded-full bg-slate-200"></span>
                                                <span class="absolute left-3 right-12 top-5 h-1 rounded-full bg-slate-200"></span>
                                                <iframe :src="src || false" tabindex="-1" title="" @load="src && $el.classList.remove('opacity-0')" class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white opacity-0 transition-opacity duration-300"
                                                        :style="`width: 842px; height: 1171px; transform: scale(${s})`" style="width: 842px; height: 1171px; transform: scale(0.2)"></iframe>
                                            @else
                                                <span class="absolute left-3 right-8 top-3 h-1 rounded-full bg-slate-200"></span>
                                                <span class="absolute left-3 right-12 top-5 h-1 rounded-full bg-slate-200"></span>
                                            @endif
                                        </span>
                                    @endforeach

                                    <span class="relative mt-[6.25rem] flex flex-1 flex-col rounded-xl bg-gradient-to-b {{ $th['front'] }} p-3 text-slate-900 shadow-[0_-6px_14px_-12px_rgba(13,37,61,0.35)] ring-1 ring-inset ring-white/70 sm:p-3.5">
                                        <span class="flex items-center justify-between gap-2">
                                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white/80 ring-1 ring-inset ring-black/5 {{ $th['ink'] }}"><i class="bx {{ $f['paperless'] ? 'bxs-file' : 'bxs-printer' }} text-lg" aria-hidden="true"></i></span>
                                            <span class="truncate rounded-md bg-white/80 px-1.5 py-0.5 text-[11px] font-medium {{ $th['ink'] }}">{{ $f['paperless'] ? 'Online' : 'Paper' }}</span>
                                        </span>
                                        <span class="mt-2 text-2xl font-semibold leading-none tabular-nums">{{ number_format($f['count']) }}</span>
                                        <span class="mt-1.5 line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-5" title="{{ $f['type'] }}">{{ $f['type'] }}</span>
                                        <span class="mt-1 block truncate text-xs tabular-nums text-slate-600">@isset($f['students']){{ $f['students'] }} {{ Str::plural('student', $f['students']) }} · @endisset latest {{ $f['latest'] }}</span>
                                    </span>
                                </button>
                            </li>
