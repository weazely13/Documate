@php
    use App\Workflow\Documents\CollegeModelInterviewRating as R;
    $in = 'w-full min-w-0 rounded-lg border border-slate-300 px-3 py-2.5 text-base outline-none focus:border-[#2A57B4] focus:ring-2 focus:ring-blue-100 sm:text-sm';
@endphp

<div class="mt-5">
    <p class="text-sm font-medium text-slate-700">Interviewees and ratings</p>
    <p class="text-xs text-slate-400">Search each interviewee and pick them from the list. Only students with an active account can be rated. Rate each from 1 (lowest) to 10 (highest). Leave unused cards empty.</p>

    <div wire:ignore wire:key="rating-grid" class="mt-3 space-y-3"
         x-data="{
            v: {}, q: {}, res: {}, info: {}, done: {}, open: null, timer: null,
            init() { this.v = { ...$wire.values }; },
            set(k) { $wire.$set('values.' + k, this.v[k] === '' || this.v[k] === undefined ? null : this.v[k], false); },
            find(i) {
                clearTimeout(this.timer);
                const t = (this.q[i] || '').trim();
                if (t.length < 2) { this.res[i] = []; this.done[i] = false; return; }
                this.timer = setTimeout(async () => {
                    this.res[i] = await $wire.searchInterviewees(t);
                    this.done[i] = true;
                    this.open = i;
                }, 300);
            },
            pick(i, s) {
                this.v['interviewee_' + i + '_user_id'] = String(s.id);
                this.v['interviewee_' + i + '_name'] = s.name;
                this.info[i] = [s.number, s.program].filter(Boolean).join(' · ');
                this.set('interviewee_' + i + '_user_id');
                this.set('interviewee_' + i + '_name');
                this.q[i] = ''; this.res[i] = []; this.done[i] = false; this.open = null;
            },
            clear(i) {
                this.v['interviewee_' + i + '_user_id'] = '';
                this.v['interviewee_' + i + '_name'] = '';
                this.info[i] = '';
                this.set('interviewee_' + i + '_user_id');
                this.set('interviewee_' + i + '_name');
            },
            total(i) {
                let s = 0;
                for (const c of ['personality', 'communication', 'talent']) {
                    const n = parseInt(this.v['score_' + i + '_' + c]);
                    if (!n) return '';
                    s += n;
                }
                return s;
            },
         }">
        @for ($i = 1; $i <= R::ROWS; $i++)
            <div wire:key="rating-card-{{ $i }}" class="relative rounded-xl border border-slate-200 bg-white p-3">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-slate-500">Interviewee {{ $i }}</span>
                    <span class="text-xs text-slate-500">Total <b class="text-slate-800" x-text="total({{ $i }}) || '-'"></b> / 30</span>
                </div>

                {{-- Picked student --}}
                <div x-show="v.interviewee_{{ $i }}_user_id" x-cloak
                     class="mt-2 flex items-start justify-between gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm">
                    <span class="min-w-0">
                        <span class="block truncate font-semibold text-slate-900" x-text="v.interviewee_{{ $i }}_name"></span>
                        <span class="block truncate text-[11px] text-emerald-700" x-text="info[{{ $i }}] || 'Verified student account'"></span>
                    </span>
                    <button type="button" @click="clear({{ $i }})" class="shrink-0 p-1 text-slate-400 hover:text-slate-700" aria-label="Change interviewee">&#10005;</button>
                </div>

                {{-- Search --}}
                <div x-show="!v.interviewee_{{ $i }}_user_id" class="mt-2">
                    <input type="text" x-model="q[{{ $i }}]" @input="find({{ $i }})" autocomplete="off"
                           placeholder="Search by name or student number" class="{{ $in }}">
                    <div x-show="open === {{ $i }} && done[{{ $i }}]" x-cloak @click.outside="open = null"
                         class="absolute left-3 right-3 z-20 mt-1 max-h-60 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                        <template x-for="s in (res[{{ $i }}] || [])" :key="s.id">
                            <button type="button" @click="pick({{ $i }}, s)"
                                    class="flex w-full items-center justify-between gap-2 px-3 py-2.5 text-left text-sm hover:bg-slate-50">
                                <span class="min-w-0">
                                    <span class="block truncate" x-text="s.name"></span>
                                    <span class="block truncate text-[11px] text-slate-400" x-text="s.program || ''"></span>
                                </span>
                                <span class="shrink-0 text-xs text-slate-400" x-text="s.number"></span>
                            </button>
                        </template>
                        <p x-show="!(res[{{ $i }}] || []).length" class="px-3 py-2 text-sm text-slate-400">No active student found.</p>
                    </div>
                </div>

                {{-- Scores --}}
                <div class="mt-3 grid grid-cols-3 gap-2">
                    @foreach (R::CRITERIA as $c => $label)
                        <div>
                            <label class="block truncate text-[10px] font-semibold uppercase tracking-wide text-slate-400" title="{{ $label }}">{{ $label }}</label>
                            <input type="number" inputmode="numeric" min="1" max="10" step="1"
                                   x-model="v.score_{{ $i }}_{{ $c }}" @change="set('score_{{ $i }}_{{ $c }}')"
                                   placeholder="1-10" class="{{ $in }} mt-1 text-center">
                        </div>
                    @endforeach
                </div>
            </div>
        @endfor
    </div>
</div>