{{-- "Your signing roles" on Home (signing accounts, and students who also sign), inside the sky: one small card per
     role with what waits on it, and the documents it signs as light outline buttons that open the inbox on that type.
     Needs: $assignments, $waitingByRole, $docsByRole. Optional: $width (max-w-* class, default max-w-4xl). --}}
@php
    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $waitingAll = (int) collect($waitingByRole)->sum();
@endphp
<section class="mx-auto {{ $width ?? 'max-w-4xl' }} text-left" aria-labelledby="roles-title">
    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h2 id="roles-title" class="text-base font-semibold text-slate-900 group-data-[ink=light]/greet:text-white">Your signing {{ $assignments->count() === 1 ? 'role' : 'roles' }}</h2>
        <a href="{{ route('signatory.inbox') }}" wire:navigate class="text-sm font-semibold text-[#2A57B4] hover:text-[#1E4090] group-data-[ink=light]/greet:text-white group-data-[ink=light]/greet:hover:text-blue-100">
            Signing inbox{{ $waitingAll ? ' · ' . $waitingAll . ' waiting' : '' }} &rarr;
        </a>
    </div>

    @if ($assignments->isNotEmpty())
        <ul class="mt-3 grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-3">
            @foreach ($assignments as $a)
                @php
                    $n = (int) ($waitingByRole[$a->signatory_role_id] ?? 0);
                    $docs = $docsByRole[$a->role->key ?? ''] ?? [];
                @endphp
                <li wire:key="hrole-{{ $a->id }}" x-data="{ all: false }" class="flex flex-col">
                    <div class="relative flex items-start gap-3 rounded-xl border border-slate-200/70 bg-white p-4 shadow-sm max-sm:flex-col max-sm:items-stretch max-sm:gap-2 max-sm:p-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-[#e2e9f7] text-[#1E4090]"><i class='bx bx-id-card text-lg' aria-hidden="true"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-900" title="{{ $a->role->label ?? 'Signing role' }}">{{ $a->role->label ?? 'Signing role' }}</p>
                            <p class="truncate text-xs text-slate-500">{{ $a->scope_name ? 'For ' . $a->scope_name : 'All students' }} · {{ $a->active_until ? 'until ' . $a->active_until->format('M j, Y') : 'no end date' }}</p>
                        </div>
                        <a href="{{ route('signatory.inbox') }}" wire:navigate
                           class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium tabular-nums ring-1 max-sm:absolute max-sm:right-2 max-sm:top-2 {{ $focus }} {{ $n ? 'bg-amber-50 text-amber-800 ring-amber-200' : 'bg-emerald-50 text-emerald-800 ring-emerald-200' }}">
                            {{ $n ? $n . ' waiting' : 'Clear' }}
                        </a>
                    </div>

                    {{-- Documents: on the sky itself, not inside the white card --}}
                    <div class="mt-2.5 px-1">
                        <p class="text-xs font-medium text-white/90">Documents you sign in this role</p>
                        @if ($docs)
                            {{-- A plain list on the sky: an arrow per document, white text --}}
                            <ul class="mt-1.5 space-y-1">
                                @foreach ($docs as $i => $d)
                                    <li @if ($i >= 4) x-show="all" x-cloak @endif>
                                        <a href="{{ route('signatory.inbox', ['type' => $d['key']]) }}" wire:navigate
                                           title="{{ $d['name'] }}{{ $d['as'] ? ' · you sign as ' . $d['as'] : '' }}"
                                           class="flex items-start gap-1.5 rounded text-xs font-medium text-white [text-shadow:0_1px_2px_rgba(13,37,61,0.35)] hover:underline underline-offset-2 {{ $focus }}">
                                            <span aria-hidden="true">&rarr;</span><span class="min-w-0">{{ $d['name'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            @if (count($docs) > 4)
                                <button type="button" @click="all = ! all" class="mt-1 rounded text-xs font-semibold text-white underline underline-offset-2 {{ $focus }}"
                                        x-text="all ? 'Show fewer' : '+{{ count($docs) - 4 }} more'">+{{ count($docs) - 4 }} more</button>
                            @endif
                        @else
                            <p class="mt-1.5 text-xs text-white/90">No student form lists this role as a fixed signer. Documents can still be routed to you.</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @else
        <p class="mt-3 rounded-xl border border-dashed border-slate-300 bg-white/80 p-6 text-center text-sm text-slate-600">You do not hold a signing role right now. The VPSD office assigns them.</p>
    @endif
</section>
