@php
    use App\Livewire\Student\Appointments\NewAppointment;
    use App\Support\AppointmentPurposes;

    $focus = 'focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-[#2A57B4]/30';
    $btn = 'inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50 [@media(pointer:coarse)]:min-h-11 ' . $focus;
    $primary = $btn . ' bg-[#2A57B4] text-white shadow-sm hover:bg-[#24499A]';
    $secondary = $btn . ' border border-slate-200 bg-white text-slate-800 shadow-sm hover:border-slate-300 hover:bg-slate-50';
    $card = 'rounded-2xl border border-slate-200 bg-white';

    $purposeObj = $this->selectedPurpose;
    $ws = $this->selectedWorkspace;

    // A document card's preview: paperless forms render their real page; paper templates show their name.
    $previewUrl = fn ($doc) => $doc->document_type ? route('student.transactions.preview', $doc->workspace_id) : null;
    $docStatus = fn ($doc) => $doc->workflow_status?->label() ?? 'Pending at the office';
    $sessionTimes = ['morning' => '8:00 AM – 12:00 PM', 'afternoon' => '1:00 PM – 5:00 PM'];
@endphp

<div class="mx-auto max-w-5xl space-y-5"
     x-data="{
        preview: { open: false, src: '', title: '' },
        showPreview(src, title) { this.preview = { open: true, src, title }; },
     }">

    {{-- Header --}}
    <div>
        <h1 class="text-xl font-semibold tracking-[-0.02em] text-slate-900 sm:text-2xl">Book an appointment</h1>
        <p class="mt-0.5 text-sm text-slate-500">Tell the VPSD office what your visit is about, pick the document it concerns, and choose a time.</p>
    </div>

    {{-- Steps --}}
    <nav aria-label="Booking steps">
        <ol class="grid grid-cols-4 gap-2">
            @foreach (NewAppointment::STEPS as $n => $label)
                @php $state = $n < $step ? 'done' : ($n === $step ? 'current' : 'todo'); @endphp
                <li @if ($state === 'current') aria-current="step" @endif>
                    <button type="button" @if ($state === 'done') wire:click="goToStep({{ $n }})" @else disabled @endif
                            class="group flex w-full flex-col gap-1.5 rounded-lg text-left {{ $state === 'done' ? 'cursor-pointer' : 'cursor-default' }} {{ $focus }}">
                        <span class="h-1 w-full rounded-full {{ $state === 'todo' ? 'bg-slate-200' : 'bg-[#2A57B4]' }}" aria-hidden="true"></span>
                        <span class="flex items-center gap-1.5 text-xs sm:text-sm">
                            <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-semibold
                                {{ $state === 'current' ? 'bg-[#2A57B4] text-white' : ($state === 'done' ? 'bg-[#e2e9f7] text-[#1E4090]' : 'bg-slate-100 text-slate-500') }}"
                                  aria-hidden="true">{{ $n }}</span>
                            <span class="truncate {{ $state === 'current' ? 'font-medium text-slate-900' : ($state === 'done' ? 'text-slate-700 group-hover:text-[#2A57B4]' : 'text-slate-500') }} max-sm:sr-only">
                                {{ $label }}
                            </span>
                            <span class="sr-only">{{ $state === 'done' ? '(done, go back)' : ($state === 'current' ? '(current step)' : '') }}</span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ol>
        <p class="mt-2 text-sm font-medium text-slate-900 sm:hidden">Step {{ $step }} of 4: {{ NewAppointment::STEPS[$step] }}</p>
    </nav>

    {{-- ============ Step 1: purpose ============ --}}
    @if ($step === 1)
        <section class="{{ $card }} p-4 sm:p-5" aria-labelledby="purpose-title">
            <h2 id="purpose-title" class="text-[15px] font-semibold text-slate-900">What is your visit about?</h2>
            <p class="mt-0.5 text-sm text-slate-500">Choose the closest one. You can explain more to the staff at the office.</p>

            @if ($ws)
                <p class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-[#f0f4fb] px-3 py-1.5 text-sm text-[#1E4090]">
                    <i class="bx bx-link text-base" aria-hidden="true"></i> For your {{ $ws->documentName() }}
                </p>
            @endif

            <fieldset class="mt-4">
                <legend class="sr-only">Purpose of your visit</legend>
                <div class="grid gap-x-6 sm:grid-cols-2">
                    @foreach ($this->purposeOptions as $o)
                        <label wire:key="p-{{ $o['key'] }}"
                               class="flex cursor-pointer items-start gap-3 border-b border-slate-100 py-3 [@media(pointer:coarse)]:min-h-11">
                            <input type="radio" name="purpose" value="{{ $o['key'] }}" @checked($purposeKey === $o['key'])
                                   wire:click="selectPurpose('{{ $o['key'] }}')"
                                   class="mt-0.5 h-4 w-4 shrink-0 border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                            <span class="min-w-0">
                                <span class="block text-sm font-medium {{ $purposeKey === $o['key'] ? 'text-[#1E4090]' : 'text-slate-900' }}">{{ $o['label'] }}</span>
                                <span class="block text-xs text-slate-500">{{ $o['description'] }}</span>
                            </span>
                        </label>
                    @endforeach
                    <label class="flex cursor-pointer items-start gap-3 border-b border-slate-100 py-3 [@media(pointer:coarse)]:min-h-11">
                        <input type="radio" name="purpose" value="{{ AppointmentPurposes::OTHER }}" @checked($purposeKey === AppointmentPurposes::OTHER)
                               wire:click="selectPurpose('{{ AppointmentPurposes::OTHER }}')"
                               class="mt-0.5 h-4 w-4 shrink-0 border-slate-300 text-[#2A57B4] focus:ring-[#2A57B4]/30">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium {{ $purposeKey === AppointmentPurposes::OTHER ? 'text-[#1E4090]' : 'text-slate-900' }}">Something else</span>
                            <span class="block text-xs text-slate-500">Describe it in your own words</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            @if ($purposeKey === AppointmentPurposes::OTHER)
                <label for="custom-purpose" class="mt-4 block text-sm font-medium text-slate-800">
                    What is your visit about? <span class="text-rose-600" aria-hidden="true">*</span>
                </label>
                <textarea id="custom-purpose" wire:model="customPurpose" rows="3" maxlength="255" required
                          aria-describedby="custom-purpose-hint @error('customPurpose') custom-purpose-error @enderror"
                          class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2.5 text-base shadow-sm focus:border-[#2A57B4] focus:ring-[#2A57B4]/20 sm:text-sm"></textarea>
                <p id="custom-purpose-hint" class="mt-1 text-xs text-slate-500">A sentence is enough, e.g. "Follow up on my organization's accreditation".</p>
                @error('customPurpose') <p id="custom-purpose-error" class="mt-1 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
            @endif

            @error('purposeKey') <p class="mt-3 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror

            <div class="mt-5 flex justify-end">
                <button type="button" wire:click="continueFromPurpose" class="{{ $primary }} max-sm:w-full" @disabled(! $purposeKey)>
                    Continue <i class="bx bx-right-arrow-alt text-base" aria-hidden="true"></i>
                </button>
            </div>
        </section>
    @endif

    {{-- ============ Step 2: document ============ --}}
    @if ($step === 2)
        @php
            $docs = $this->documents;
            $all = $docs['match']->concat($docs['other']);
            $startable = collect($purposeObj['document_types'] ?? [])
                ->map(fn ($t) => \App\Workflow\Documents\DocumentRegistry::find($t))
                ->filter(fn ($d) => $d && ! $d->issuedByOffice() && ! $d->retired())
                ->take(3);
        @endphp
        <section class="{{ $card }} p-4 sm:p-5" aria-labelledby="doc-title">
            <h2 id="doc-title" class="text-[15px] font-semibold text-slate-900">Which document is it about?</h2>
            <p class="mt-0.5 text-sm text-slate-500">
                Your visit: <span class="font-medium text-slate-800">{{ $purpose }}</span>.
                {{ $docs['specific'] ? 'These are your documents that fit it.' : 'Choose one if the visit is about it, or continue without one.' }}
            </p>

            @error('workspaceId') <p class="mt-3 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-800" role="alert">{{ $message }}</p> @enderror

            @if ($all->isEmpty())
                <div class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center">
                    <i class="bx bx-file text-3xl text-slate-400" aria-hidden="true"></i>
                    <p class="mt-2 text-sm font-medium text-slate-900">No documents for this yet</p>
                    <p class="mt-0.5 text-sm text-slate-500">
                        {{ $startable->isNotEmpty() ? 'Start one first so the office can look at it before your visit, or book without one.' : 'You can still book the visit without a document.' }}
                    </p>
                    @if ($startable->isNotEmpty())
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            @foreach ($startable as $d)
                                <a href="{{ route('student.transactions.new', $d->key()) }}" wire:navigate class="{{ $secondary }}">
                                    <i class="bx bx-plus text-base" aria-hidden="true"></i> {{ $d->name() }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @else
                {{-- Portrait A4 cards: two across on phones, more on wider screens --}}
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4" role="radiogroup" aria-labelledby="doc-title">
                    @foreach ($all as $doc)
                        @php
                            $on = $workspaceId === $doc->workspace_id;
                            $src = $previewUrl($doc);
                            $isMatch = $docs['match']->contains('workspace_id', $doc->workspace_id);
                        @endphp
                        <div wire:key="doc-{{ $doc->workspace_id }}"
                             class="group relative aspect-[210/297] overflow-hidden rounded-xl border bg-[#e7e5e1] transition-shadow
                                 {{ $on ? 'border-[#2A57B4] shadow-[0_0_0_2px_#2A57B4]' : 'border-slate-200 hover:shadow-md' }}"
                             x-data="{ s: .2 }" x-init="new ResizeObserver(() => s = $el.clientWidth / 842).observe($el)">
                            @if ($src)
                                <iframe src="{{ $src }}" loading="lazy" tabindex="-1" title="" aria-hidden="true"
                                        class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white"
                                        :style="`width:842px;height:1191px;transform:scale(${s})`"></iframe>
                            @else
                                <div class="absolute inset-0 grid place-items-center bg-white" aria-hidden="true">
                                    <span class="flex flex-col items-center gap-1 text-slate-500">
                                        <i class="bx bx-file text-4xl"></i>
                                        <span class="text-xs">Paper form</span>
                                    </span>
                                </div>
                            @endif

                            {{-- Name and status over the bottom of the page --}}
                            <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}"
                                    wire:click="selectDocument({{ $doc->workspace_id }})"
                                    class="absolute inset-0 flex flex-col justify-end rounded-xl text-left {{ $focus }}">
                                <span class="block bg-gradient-to-t from-white via-white/95 to-white/0 px-2.5 pb-2.5 pt-6">
                                    <span class="block truncate text-sm font-medium text-slate-900">{{ $doc->documentName() }}</span>
                                    <span class="block truncate text-[11px] text-slate-600">
                                        {{ $doc->transaction_code ? $doc->transaction_code . ' · ' : '' }}{{ $docStatus($doc) }}
                                    </span>
                                </span>
                            </button>

                            <span class="pointer-events-none absolute left-2 top-2 flex flex-wrap gap-1">
                                @if ($on)
                                    <span class="rounded-full bg-[#2A57B4] px-2 py-0.5 text-[11px] font-medium text-white">Selected</span>
                                @elseif ($isMatch && ! $docs['specific'])
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-800 ring-1 ring-emerald-200">Fits your visit</span>
                                @endif
                            </span>
                            @if ($src)
                                <button type="button" @click.stop="showPreview(@js($src), @js($doc->documentName()))"
                                        class="absolute right-1.5 top-1.5 grid h-9 w-9 place-items-center rounded-lg bg-white/95 text-slate-700 shadow-sm hover:text-[#2A57B4] [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}"
                                        aria-label="Preview {{ $doc->documentName() }}{{ $doc->transaction_code ? ', ' . $doc->transaction_code : '' }}">
                                    <i class="bx bx-show text-lg" aria-hidden="true"></i>
                                </button>
                            @elseif ($doc->generated_pdf_path)
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($doc->generated_pdf_path) }}" target="_blank" rel="noopener"
                                   class="absolute right-1.5 top-1.5 grid h-9 w-9 place-items-center rounded-lg bg-white/95 text-slate-700 shadow-sm hover:text-[#2A57B4] [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}"
                                   aria-label="Open the PDF of {{ $doc->documentName() }}">
                                    <i class="bx bx-link-external text-lg" aria-hidden="true"></i>
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($docs['hidden'] > 0)
                <p class="mt-3 text-sm text-slate-500">
                    Showing your {{ $docs['other']->count() }} most recent documents. To find an older one, go back and choose the purpose it belongs to.
                </p>
            @endif

            <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <button type="button" wire:click="goToStep(1)" class="{{ $secondary }}">
                    <i class="bx bx-left-arrow-alt text-base" aria-hidden="true"></i> Back
                </button>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
                    @if ($workspaceId)
                        <button type="button" wire:click="clearDocument" class="{{ $btn }} text-slate-600 hover:bg-slate-100">Don't attach a document</button>
                    @endif
                    <button type="button" wire:click="continueFromDocument" class="{{ $primary }}">
                        {{ $workspaceId ? 'Continue' : 'Continue without a document' }} <i class="bx bx-right-arrow-alt text-base" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Step 3: date and time ============ --}}
    @if ($step === 3)
        @php $dayData = $selectedDate ? collect($this->calendarDays)->firstWhere('date', $selectedDate) : null; @endphp
        <section class="grid items-start gap-4 lg:grid-cols-[minmax(0,1fr)_340px]" aria-labelledby="date-title">
            <div class="{{ $card }} p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="date-title" class="text-[15px] font-semibold text-slate-900">Choose a day</h2>
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="prevMonth" @disabled(! $canGoBackMonth) aria-label="Previous month"
                                class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 disabled:opacity-40 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
                            <i class="bx bx-chevron-left text-xl" aria-hidden="true"></i>
                        </button>
                        <p class="w-32 text-center text-sm font-medium text-slate-900" aria-live="polite">{{ $monthLabel }}</p>
                        <button type="button" wire:click="nextMonth" aria-label="Next month"
                                class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}">
                            <i class="bx bx-chevron-right text-xl" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-7 gap-1 text-center text-xs font-medium text-slate-500" aria-hidden="true">
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $wd)
                        <div>{{ $wd }}</div>
                    @endforeach
                </div>
                <div class="mt-1 grid grid-cols-7 gap-1">
                    @foreach ($this->calendarDays as $day)
                        @php
                            $on = $selectedDate === $day['date'];
                            $off = ! $day['inMonth'] || ! $day['has_available'];
                            $left = ($day['morning_open'] ? $day['morning_remaining'] : 0) + ($day['afternoon_open'] ? $day['afternoon_remaining'] : 0);
                            $label = \Carbon\Carbon::parse($day['date'])->format('l, F j') . ($off ? ', not available' : ', ' . $left . ' slots left');
                        @endphp
                        <button type="button" wire:key="cal-{{ $day['date'] }}"
                                @if (! $off) wire:click="selectDate('{{ $day['date'] }}')" @endif
                                @disabled($off) aria-label="{{ $label }}" aria-pressed="{{ $on ? 'true' : 'false' }}"
                                class="relative flex aspect-square min-h-10 flex-col items-center justify-center rounded-lg text-sm transition-colors sm:aspect-auto sm:h-14 {{ $focus }}
                                    {{ ! $day['inMonth'] ? 'invisible' : '' }}
                                    {{ $on ? 'bg-[#2A57B4] font-semibold text-white' : ($off ? 'cursor-not-allowed text-slate-400' : 'border border-slate-200 text-slate-800 hover:border-[#2A57B4] hover:bg-[#f0f4fb]') }}">
                            <span class="tabular-nums">{{ $day['day'] }}</span>
                            @if (! $off)
                                <span class="mt-0.5 hidden text-[10px] tabular-nums sm:block {{ $on ? 'text-white/85' : 'text-emerald-700' }}">{{ $left }} left</span>
                                <span class="mt-0.5 h-1 w-1 rounded-full sm:hidden {{ $on ? 'bg-white' : 'bg-emerald-600' }}" aria-hidden="true"></span>
                            @endif
                            @if ($day['isToday'] && ! $on)
                                <span class="absolute inset-x-3 bottom-1 h-0.5 rounded-full bg-[#2A57B4]" aria-hidden="true"></span>
                            @endif
                        </button>
                    @endforeach
                </div>
                <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-600" aria-hidden="true"></span> Open</span>
                    <span class="inline-flex items-center gap-1.5"><span class="h-0.5 w-3 rounded-full bg-[#2A57B4]" aria-hidden="true"></span> Today</span>
                    <span>Closed on weekends and full days.</span>
                </p>
            </div>

            <div class="{{ $card }} p-4 sm:p-5">
                <h3 class="text-[15px] font-semibold text-slate-900">
                    {{ $selectedDate ? \Carbon\Carbon::parse($selectedDate)->format('l, F j') : 'Choose a time' }}
                </h3>
                @if (! $dayData)
                    <p class="mt-1 text-sm text-slate-500">Pick a day on the calendar to see its sessions.</p>
                @else
                    <div class="mt-3 space-y-2" role="radiogroup" aria-label="Session">
                        @foreach (['morning' => 'Morning', 'afternoon' => 'Afternoon'] as $key => $name)
                            @php $open = $dayData[$key . '_open']; $on = $selectedSession === $key; @endphp
                            <button type="button" role="radio" aria-checked="{{ $on ? 'true' : 'false' }}"
                                    wire:click="selectSession('{{ $key }}')" @disabled(! $open)
                                    class="flex w-full items-center justify-between gap-3 rounded-xl border p-3 text-left transition-colors {{ $focus }}
                                        {{ ! $open ? 'cursor-not-allowed border-slate-100 bg-slate-50 opacity-60' : ($on ? 'border-[#2A57B4] bg-[#f0f4fb] shadow-[0_0_0_1px_#2A57B4]' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50') }}">
                                <span>
                                    <span class="block text-sm font-medium text-slate-900">{{ $name }}</span>
                                    <span class="block text-xs text-slate-500">{{ $sessionTimes[$key] }}</span>
                                </span>
                                <span class="shrink-0 text-xs font-medium {{ $open ? 'text-emerald-700' : 'text-slate-500' }}">
                                    {{ $open ? $dayData[$key . '_remaining'] . ' slots left' : 'Unavailable' }}
                                </span>
                            </button>
                        @endforeach
                    </div>
                @endif
                @error('selectedDate') <p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror
                @error('selectedSession') <p class="mt-2 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror

                <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-between">
                    <button type="button" wire:click="goToStep(2)" class="{{ $secondary }}">
                        <i class="bx bx-left-arrow-alt text-base" aria-hidden="true"></i> Back
                    </button>
                    <button type="button" wire:click="continueFromDate" class="{{ $primary }}" @disabled(! $selectedSession)>
                        Continue <i class="bx bx-right-arrow-alt text-base" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </section>
    @endif

    {{-- ============ Step 4: review ============ --}}
    @if ($step === 4)
        <section class="{{ $card }} p-4 sm:p-5" aria-labelledby="review-title">
            <h2 id="review-title" class="text-[15px] font-semibold text-slate-900">Review your appointment</h2>
            <p class="mt-0.5 text-sm text-slate-500">The office reviews your request and gives you a queue number once it is approved.</p>

            <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_240px]">
                <dl class="divide-y divide-slate-100 text-sm">
                    @foreach ([
                        'Purpose' => [$purpose, 1],
                        'Document' => [$ws ? $ws->documentName() . ($ws->transaction_code ? ' · ' . $ws->transaction_code : '') : 'None attached', 2],
                        'Date' => [\Carbon\Carbon::parse($selectedDate)->format('l, F j, Y'), 3],
                        'Session' => [ucfirst($selectedSession) . ' · ' . $sessionTimes[$selectedSession], 3],
                    ] as $label => [$value, $stepNo])
                        <div class="grid grid-cols-[96px_minmax(0,1fr)_auto] items-baseline gap-3 py-2.5">
                            <dt class="text-slate-500">{{ $label }}</dt>
                            <dd class="min-w-0 break-words text-slate-900">{{ $value }}</dd>
                            <dd>
                                <button type="button" wire:click="goToStep({{ $stepNo }})"
                                        class="rounded px-1 text-sm font-medium text-[#2A57B4] hover:underline {{ $focus }}">
                                    Change<span class="sr-only"> {{ strtolower($label) }}</span>
                                </button>
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @if ($ws && $previewUrl($ws))
                    <button type="button" @click="showPreview(@js($previewUrl($ws)), @js($ws->documentName()))"
                            class="group relative h-56 overflow-hidden rounded-xl border border-slate-200 bg-[#e7e5e1] text-left {{ $focus }}"
                            aria-label="Preview {{ $ws->documentName() }}">
                        <iframe src="{{ $previewUrl($ws) }}" loading="lazy" tabindex="-1" title="" aria-hidden="true"
                                class="pointer-events-none absolute left-1/2 top-2 origin-top border-0 bg-white"
                                style="width: 842px; height: 1171px; transform: translateX(-50%) scale(.27);"></iframe>
                        <span class="absolute inset-x-0 bottom-0 flex items-center justify-center gap-1 bg-white/95 py-2 text-sm font-medium text-[#2A57B4]">
                            <i class="bx bx-show text-base" aria-hidden="true"></i> Preview document
                        </span>
                    </button>
                @endif
            </div>

            @error('selectedSession') <p class="mt-3 text-sm text-rose-700" role="alert">{{ $message }}</p> @enderror

            <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-between">
                <button type="button" wire:click="goToStep(3)" class="{{ $secondary }}">
                    <i class="bx bx-left-arrow-alt text-base" aria-hidden="true"></i> Back
                </button>
                <button type="button" wire:click="confirmAppointment" wire:loading.attr="disabled" wire:target="confirmAppointment" class="{{ $primary }}">
                    <span wire:loading.remove wire:target="confirmAppointment">Book appointment</span>
                    <span wire:loading wire:target="confirmAppointment">Booking…</span>
                </button>
            </div>
        </section>
    @endif

    {{-- Document preview --}}
    <template x-teleport="body">
        <div x-show="preview.open" x-cloak x-transition.opacity
             class="fixed inset-0 z-[90] flex flex-col bg-slate-900/50 sm:p-6"
             role="dialog" aria-modal="true" aria-labelledby="appt-preview-title"
             @keydown.escape.window="preview.open = false">
            <div class="mx-auto flex h-full w-full max-w-4xl flex-col overflow-hidden bg-white shadow-2xl sm:rounded-2xl" @click.outside="preview.open = false">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5">
                    <div class="min-w-0">
                        <h2 id="appt-preview-title" class="truncate text-sm font-semibold text-slate-900" x-text="preview.title"></h2>
                        <p class="text-xs text-slate-500">A preview of your document as the office sees it.</p>
                    </div>
                    <button type="button" @click="preview.open = false" x-effect="preview.open && $nextTick(() => $el.focus())"
                            class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-900 [@media(pointer:coarse)]:h-11 [@media(pointer:coarse)]:w-11 {{ $focus }}"
                            aria-label="Close preview">
                        <i class="bx bx-x text-2xl" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto bg-[#e7e5e1] p-3 sm:p-5"
                     x-data="{ w: 842, h: 1171, scale: 1, fit() { const cw = $el.clientWidth - 24; if (cw > 0) this.scale = Math.min(1, cw / this.w); } }"
                     x-init="new ResizeObserver(() => fit()).observe($el)"
                     @message.window="if ($refs.f && $event.source === $refs.f.contentWindow && $event.data && $event.data.documatePreviewHeight) h = Math.ceil($event.data.documatePreviewHeight)">
                    <template x-if="preview.open">
                        <div class="mx-auto overflow-hidden" :style="`width:${w * scale}px;height:${h * scale}px`" x-init="fit()">
                            <iframe x-ref="f" :src="preview.src" title="Document preview" class="block border-0 bg-[#e7e5e1]"
                                    :style="`width:${w}px;height:${h}px;transform:scale(${scale});transform-origin:top left;`"></iframe>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
