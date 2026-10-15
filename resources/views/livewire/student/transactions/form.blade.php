@php
    use App\Enums\ApprovalStatus;
    use App\Enums\TransactionStatus;

    $badge = match ($status) {
        TransactionStatus::Draft => 'bg-slate-100 text-slate-600 ring-slate-200',
        TransactionStatus::Submitted => 'bg-blue-50 text-blue-700 ring-blue-200',
        TransactionStatus::UnderReview => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        TransactionStatus::ActionRequired => 'bg-amber-50 text-amber-700 ring-amber-200',
        TransactionStatus::Approved => 'bg-teal-50 text-teal-700 ring-teal-200',
        TransactionStatus::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        TransactionStatus::Rejected => 'bg-rose-50 text-rose-700 ring-rose-200',
        TransactionStatus::Voided => 'bg-slate-200 text-slate-600 ring-slate-300',
        default => 'bg-slate-100 text-slate-600 ring-slate-200',
    };
    $editable = $this->isEditable;
    $document = $this->document;
    $input = 'mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100';
@endphp

<div class="mx-auto w-full max-w-2xl space-y-5">

    {{-- Header --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('student.transactions.index') }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; Transactions</a>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $document->name() }}</h2>
            <p class="font-mono text-xs text-slate-400">{{ $ws?->transaction_code ?? 'Not saved yet' }}</p>
        </div>
        <span class="rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $badge }}">{{ $status?->label() ?? 'New' }}</span>
    </div>
    @if ($this->document->studentNote())
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">
            {{ $this->document->studentNote() }}
        </div>
    @endif

    @if (session('workflow_message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('workflow_message') }}</div>
    @endif
    @error('form')
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
    @enderror

    {{-- Messages from the office --}}
    @if ($status === TransactionStatus::ActionRequired && $ws->workflow_note)
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-700">The VPSD office needs changes</p>
            <p class="mt-1 text-sm text-amber-900">{{ $ws->workflow_note }}</p>
        </div>
    @elseif ($status === TransactionStatus::Rejected && $ws->workflow_note)
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-rose-700">Rejected</p>
            <p class="mt-1 text-sm text-rose-900">{{ $ws->workflow_note }}</p>
        </div>
    @elseif ($status === TransactionStatus::Completed)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <div>
                <p class="text-sm font-semibold text-emerald-800">Completed and signed</p>
                <p class="text-xs text-emerald-700">Save your copy as PDF. Print it only if a physical copy is required.</p>
            </div>
            <button type="button" wire:click="downloadPdf" wire:loading.attr="disabled"
                    class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                <span wire:loading.remove wire:target="downloadPdf">Download PDF</span>
                <span wire:loading wire:target="downloadPdf">Preparing…</span>
            </button>
            @if ($ws->verification_token)
                <a href="{{ route('document.verify', $ws->verification_token) }}" target="_blank" class="text-xs font-semibold text-emerald-700 hover:underline">View verification page</a>
            @endif
        </div>
    @elseif ($status === TransactionStatus::Voided)
        <div class="rounded-xl border border-slate-300 bg-slate-100 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-600">Voided by the VPSD office</p>
            <p class="mt-1 text-sm text-slate-800">{{ $ws->void_reason }}</p>
            @if ($ws->superseded_by)
                <a href="{{ route('student.transactions.show', $ws->superseded_by) }}" wire:navigate
                class="mt-2 inline-block text-sm font-semibold text-[#2A57B4] hover:underline">Open the corrected draft &rarr;</a>
            @endif
        </div>
    @endif
    @if (! empty($afterReviewNote))
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Next step</p>
            <p class="mt-1 text-sm text-blue-900">{{ $afterReviewNote }}</p>
        </div>
    @endif

    {{-- Progress --}}
    @if ($progress !== null)
        <ol class="grid grid-cols-4 gap-2">
            @foreach (['Submitted', 'VPSD review', 'Signatures', 'Completed'] as $i => $label)
                @php
                    $done = $i === 0 ? true : $progress > $i;
                    $active = $i !== 0 && $progress === $i;
                @endphp
                <li class="text-center">
                    <div class="h-1.5 rounded-full {{ $done ? 'bg-emerald-500' : ($active ? 'bg-[#2A57B4] animate-pulse' : 'bg-slate-200') }}"></div>
                    <p class="mt-1.5 text-[11px] font-semibold {{ $done || $active ? 'text-slate-700' : 'text-slate-400' }}">{{ $label }}</p>
                </li>
            @endforeach
        </ol>
    @endif

    {{-- The form --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="rounded-xl bg-slate-50 p-3.5">
            <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">From your account</p>
            <p class="mt-1 text-sm font-semibold text-slate-900">{{ $student['student_name'] ?: '—' }}</p>
            <p class="text-sm text-slate-600">{{ $student['student_program'] ?: 'Program, year and section not set' }}</p>
            @if ($editable && ! $student['student_program'])
                <a href="{{ route('profile') }}" wire:navigate class="mt-1 inline-block text-xs font-semibold text-[#2A57B4] hover:underline">Update your profile</a>
            @endif
            @if (in_array($document->key(), ['permit_to_conduct_activity', 'permit_to_conduct_off_campus'], true))
                @if (! empty($student['officer_position']))
                    <p class="mt-1.5 text-sm text-slate-600">
                        <span class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold uppercase text-[#2A57B4]">Officer</span>
                        {{ $student['officer_position'] }}, {{ $student['officer_organization'] }}
                    </p>
                @else
                    <p class="mt-1.5 text-xs text-amber-700">You are not an officer. Organization-hosted permits can only be requested by officers.</p>
                @endif
            @endif
        </div>

        <div class="mt-5 space-y-4">
            @foreach ($document->fields() as $name => $f)
                @php
                    $dep = $f['show_when'] ?? null;
                    $depKey = $dep ? array_key_first($dep) : null;
                    $depVal = $dep ? $dep[$depKey] : null;

                    $hide = $f['hide_when'] ?? null;
                    $hiddenNow = $hide && in_array((string) ($values[$hide['field']] ?? ''), $hide['values'], true);

                    $xShow = null;
                    if ($dep) {
                        $xShow = '$wire.values.' . $depKey . ' === ' . json_encode($depVal);
                    }
                    if ($hide) {
                        $xShow = ($xShow ? $xShow . ' && ' : '') . '!' . json_encode($hide['values']) . '.includes($wire.values.' . $hide['field'] . ')';
                    }
                @endphp
                @continue(! empty($f['staff_only']))
                @continue(! $editable && (($dep && ($values[$depKey] ?? null) !== $depVal) || $hiddenNow))
                <div wire:key="f-{{ $name }}"
                    @if ($editable && $xShow) x-show="{{ $xShow }}" x-cloak @endif>
                    @if ($f['type'] === 'checkbox')
                        @if ($editable)
                            <label class="flex items-center gap-2.5 text-sm font-medium text-slate-700">
                                <input type="checkbox" wire:model="values.{{ $name }}" class="h-4 w-4 accent-[#2A57B4]">
                                {{ $f['label'] }}
                            </label>
                        @else
                            <p class="text-sm text-slate-800">
                                <span class="font-medium text-slate-700">{{ $f['label'] }}:</span>
                                {{ $document->formatValue($f, $values[$name] ?? null) }}
                            </p>
                        @endif
                    @else
                        <label class="text-sm font-medium text-slate-700">
                            {{ $f['label'] }} @if ($editable && ($f['required'] ?? false))<span class="text-rose-500">*</span>@endif
                        </label>

                        @if ($editable)
                            @if ($f['type'] === 'textarea')
                                <textarea wire:model="values.{{ $name }}" rows="4" maxlength="{{ $f['max'] ?? 500 }}" class="{{ $input }}"></textarea>
                            @elseif ($f['type'] === 'select')
                                <select wire:model="values.{{ $name }}" class="{{ $input }}">
                                    <option value="">Choose…</option>
                                    @foreach ($f['options'] as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </select>
                            @else
                                <input wire:model="values.{{ $name }}"
                                    type="{{ ['date' => 'date', 'time' => 'time'][$f['type']] ?? 'text' }}"
                                    @if ($f['type'] === 'date')
                                        @if (! empty($f['future'])) min="{{ now()->toDateString() }}"
                                        @else max="{{ now()->toDateString() }}" @endif
                                    @endif
                                    @if (isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                                    placeholder="{{ $f['placeholder'] ?? '' }}" class="{{ $input }}">
                            @endif
                            @if (! empty($f['help'])) <p class="mt-1 text-xs text-slate-400">{{ $f['help'] }}</p> @endif
                        @else
                            <p class="mt-1 whitespace-pre-wrap rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm text-slate-800">{{ $document->formatValue($f, $values[$name] ?? null) ?: '—' }}</p>
                        @endif
                    @endif
                    @error("values.$name") <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        @if ($document->requiresStudentSignature())
        <div class="mt-5">
            <label class="text-sm font-medium text-slate-700">
                Your signature @if ($editable)<span class="text-rose-500">*</span>@endif
            </label>

            @if ($savedSignature)
                <img src="{{ $savedSignature }}" alt="Your signature"
                    class="mt-2 h-16 rounded-lg border border-slate-200 bg-white p-1">
                @if ($editable)
                    <p class="mt-1 text-xs text-slate-400">Signature on file. Draw again below only if you want to replace it.</p>
                @endif
            @endif

            @if ($editable)
                <p class="text-xs text-slate-400">Draw with your finger, stylus or mouse.</p>
                <div wire:ignore class="mt-2"
                    x-data="{
                        drawing: false, has: false, ctx: null,
                        init() { const c = this.$refs.c; this.ctx = c.getContext('2d'); this.ctx.lineWidth = 3; this.ctx.lineCap = 'round'; this.ctx.lineJoin = 'round'; this.ctx.strokeStyle = '#111'; },
                        pos(e) { const c = this.$refs.c, r = c.getBoundingClientRect(); return [(e.clientX - r.left) * c.width / r.width, (e.clientY - r.top) * c.height / r.height]; },
                        start(e) { this.drawing = true; const [x, y] = this.pos(e); this.ctx.beginPath(); this.ctx.moveTo(x, y); this.ctx.lineTo(x + 0.1, y + 0.1); this.ctx.stroke(); },
                        move(e) { if (!this.drawing) return; const [x, y] = this.pos(e); this.ctx.lineTo(x, y); this.ctx.stroke(); this.has = true; },
                        end() { if (!this.drawing) return; this.drawing = false; if (this.has) { $wire.$set('studentSignature', this.$refs.c.toDataURL('image/png'), false); } },
                        clear() { this.ctx.clearRect(0, 0, 600, 200); this.has = false; $wire.$set('studentSignature', '', false); }
                    }">
                    <canvas x-ref="c" width="600" height="200"
                            class="w-full touch-none rounded-xl border border-dashed border-slate-300 bg-white"
                            @pointerdown.prevent="start($event)" @pointermove.prevent="move($event)"
                            @pointerup="end()" @pointerleave="end()"></canvas>
                    <button type="button" @click="clear()" class="mt-1.5 text-xs font-medium text-slate-500 hover:text-rose-600">Clear signature</button>
                </div>
            @endif

            @error('studentSignature') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
        </div>
    @endif
    @if ($document->requiresItemPhoto())
        <div class="mt-5">
            <label class="text-sm font-medium text-slate-700">
                Photo of the item @if ($editable)<span class="text-rose-500">*</span>@endif
            </label>

            @if ($itemPhoto)
                <img src="{{ $itemPhoto->temporaryUrl() }}" alt="Item photo preview"
                    class="mt-2 max-h-56 rounded-xl border border-slate-200 bg-white object-contain p-1">
            @elseif ($hasPhoto)
                <img src="{{ route('workflow.item-photo', $ws->workspace_id) }}?v={{ $ws->updated_at?->timestamp }}" alt="Item photo"
                    class="mt-2 max-h-56 rounded-xl border border-slate-200 bg-white object-contain p-1">
            @endif

            @if ($editable)
                <input type="file" wire:model="itemPhoto" accept="image/png,image/jpeg" capture="environment"
                    class="mt-2 block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-[#2A57B4] file:px-3.5 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#24499A]">
                <p class="mt-1 text-xs text-slate-400">On a phone this opens the camera. JPG or PNG, up to 5 MB. Not printed on the form.</p>
                <div wire:loading wire:target="itemPhoto" class="mt-1 text-xs text-[#2A57B4]">Uploading photo…</div>
            @endif

            @error('itemPhoto') <p class="mt-1 text-xs font-medium text-rose-500">{{ $message }}</p> @enderror
        </div>
    @endif

    @if ($editable)
        <div class="mt-6 flex flex-wrap gap-2">
            <button type="button" wire:click="saveDraft" wire:loading.attr="disabled"
                    class="flex-1 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50">Save draft</button>
            <button type="button" wire:click="submit" wire:loading.attr="disabled"
                    wire:confirm="Submit to the VPSD office? You can't edit it afterwards unless they ask for changes."
                    class="flex-1 rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#24499A] disabled:opacity-50">
                {{ $status === TransactionStatus::ActionRequired ? 'Resubmit' : 'Submit' }}
            </button>
        </div>
    @endif
</section>

    {{-- Signatures --}}
    @if ($approvals->isNotEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-400">Signatures</h3>
            <ol class="mt-3 space-y-3">
                @foreach ($approvals as $a)
                    @php
                        $dot = match ($a->status) {
                            ApprovalStatus::Approved => 'bg-emerald-500',
                            ApprovalStatus::Pending => 'bg-blue-500 animate-pulse',
                            ApprovalStatus::Rejected => 'bg-rose-500',
                            default => 'bg-slate-300',
                        };
                        $text = match ($a->status) {
                            ApprovalStatus::Approved => 'Signed' . ($a->acted_at ? ' · ' . $a->acted_at->format('M j, g:i A') : ''),
                            ApprovalStatus::Pending => 'Waiting for signature',
                            ApprovalStatus::Rejected => 'Rejected',
                            ApprovalStatus::Skipped => 'Cancelled',
                            default => 'Not started',
                        };
                    @endphp
                    <li class="flex items-start gap-3" wire:key="appr-{{ $a->id }}">
                        <span class="mt-1.5 h-2.5 w-2.5 shrink-0 rounded-full {{ $dot }}"></span>
                        <div>
                            <p class="text-sm font-medium text-slate-800">{{ $a->role->label ?? $a->label }}</p>
                            <p class="text-xs text-slate-500">{{ $text }}</p>
                            @if ($a->status === ApprovalStatus::Rejected && $a->reason)
                                <p class="mt-1 text-xs text-rose-600">{{ $a->reason }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    {{-- Activity --}}
    @if (count($timeline))
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-400">Activity</h3>
            <div class="mt-3 space-y-3">
                @foreach ($timeline as $item)
                    <div class="relative pl-5">
                        <span class="absolute left-0 top-1.5 h-2 w-2 rounded-full bg-slate-300"></span>
                        <p class="text-sm font-medium text-slate-800">{{ $item['text'] }}</p>
                        <p class="text-xs text-slate-400">{{ $item['at']->format('M j, Y g:i A') }}</p>
                        @if ($item['note']) <p class="mt-0.5 text-xs text-slate-500">{{ $item['note'] }}</p> @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>