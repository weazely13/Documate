@php
    $btn = 'inline-flex min-h-[44px] items-center justify-center rounded-xl px-4 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50';
    $field = 'mt-1.5 block w-full rounded-xl border border-slate-300 bg-white px-3.5 py-3 text-base outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100 sm:py-2.5 sm:text-sm';
    $isMine = (int) $approval->acted_by === (int) auth()->id();
    $actionBlocked = ! $signatureReady || ($canSchedule && ! $schedDate);
@endphp

{{-- pb-32 leaves room for the sticky action bar on phones --}}
<div class="mx-auto max-w-3xl space-y-4 sm:space-y-5 {{ $canAct ? 'pb-32 sm:pb-0' : '' }}">

    {{-- Header --}}
    <div>
        <a href="{{ route('signatory.inbox') }}" wire:navigate
           class="-ml-1 inline-flex min-h-[44px] items-center gap-0.5 pr-3 text-sm font-medium text-slate-500 hover:text-[#2A57B4]">
            <i class='bx bx-chevron-left text-xl'></i> Signing Inbox
        </a>
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{{ $document->name() }}</h2>
                <p class="mt-0.5 font-mono text-xs text-slate-400">{{ $ws->transaction_code }}</p>
            </div>
            <div class="shrink-0">@include('livewire.workflow._badge', ['status' => $ws->workflow_status])</div>
        </div>
    </div>

    {{-- Messages --}}
    @if (session('signing_message'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('signing_message') }}</div>
    @endif

    @error('action')
        @if (! $modal)
            <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
        @endif
    @enderror

    @if ($canAct && ! $signatureReady)
        <div class="flex flex-col gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-amber-900">Add your e-signature in your profile before you can approve this.</p>
            <a href="{{ route('profile') }}" wire:navigate class="{{ $btn }} bg-amber-600 text-white hover:bg-amber-700">Open profile</a>
        </div>
    @endif
    @if ($canAct && $document->signingNote())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-medium text-amber-900">{{ $document->signingNote() }}</div>
    @endif

    @if (! $canAct)
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
            @if ($isMine)
                You {{ $approval->status->value === 'approved' ? 'signed' : 'rejected' }} this on {{ $approval->acted_at?->format('M j, Y g:i A') }}.
            @else
                This step is no longer waiting for your action.
            @endif
        </div>
    @endif

    {{-- Preview --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <p class="text-xs font-medium text-slate-500">You are signing as</p>
        <h3 class="text-sm font-semibold text-slate-900">{{ $approval->role->label ?? 'Account signer' }} <span class="font-normal text-slate-500">({{ $approval->label }})</span></h3>
        <div class="mt-4 border-t border-slate-100 pt-4">@include('livewire.workflow._preview', [
            'src' => route('signatory.preview', $approval->id),
            'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
        ])</div>
    </section>

    {{-- Approval chain --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h3 class="text-sm font-semibold text-slate-900">Approval chain</h3>
        <div class="mt-3">@include('livewire.workflow._chain', ['approvals' => $approvals])</div>
    </section>

    {{-- Committee members --}}
    @if ($canSend)
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="text-sm font-semibold text-slate-900">Committee members</h3>
            <p class="mt-0.5 text-xs text-slate-500">
                Send each member a secure link to review the report and sign. They confirm their email with a one-time code first.
                Verify who they are before you send.
            </p>

            @if ($notice)
                <div role="status" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm font-medium text-emerald-800">{{ $notice }}</div>
            @endif

            <ul class="mt-4 space-y-3">
                @foreach ($members as $m)
                    @php
                        $req = $requests->get($m->id);
                        $live = $req && $req->status === 'pending' && $req->expires_at->isFuture();
                        $v = $m->status->value;
                    @endphp
                    <li wire:key="member-{{ $m->id }}" class="rounded-xl border border-slate-200 p-3.5">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $m->signer_name ?: $m->external_name }}</p>
                                <p class="break-all text-xs text-slate-500">{{ $m->external_email }}</p>
                                <p class="mt-1 text-xs font-medium {{ $v === 'approved' ? 'text-emerald-600' : ($v === 'rejected' ? 'text-rose-600' : ($live ? 'text-blue-600' : 'text-amber-600')) }}">
                                    @if ($v === 'approved') Signed {{ $m->acted_at?->format('M j, g:i A') }}
                                    @elseif ($v === 'rejected') Declined
                                    @elseif ($live) Link sent {{ $req->created_at->diffForHumans() }}, expires {{ $req->expires_at->format('M j, g:i A') }}
                                    @else Link not sent yet
                                    @endif
                                </p>
                            </div>

                            @if ($v === 'pending')
                                @php $ready = $verified->get($m->id, false); @endphp
                                <div class="grid shrink-0 grid-cols-2 gap-2 sm:flex">
                                    <button type="button" wire:click="startEdit({{ $m->id }})"
                                            class="{{ $btn }} border border-slate-300 bg-white !px-3 text-slate-700 hover:bg-slate-50">Fix email</button>

                                    @if ($ready)
                                        <button type="button" wire:click="resendLink({{ $m->id }})" wire:loading.attr="disabled" wire:target="resendLink({{ $m->id }})"
                                                @if ($live) wire:confirm="Email a new link? The previous link will stop working." @endif
                                                class="{{ $btn }} bg-[#2A57B4] !px-3 text-white hover:bg-[#24499A]">
                                            <span wire:loading.remove wire:target="resendLink({{ $m->id }})">{{ $req ? 'Resend link' : 'Send link' }}</span>
                                            <span wire:loading wire:target="resendLink({{ $m->id }})">Sending…</span>
                                        </button>
                                    @else
                                        <button type="button" wire:click="openSend({{ $m->id }})"
                                                class="{{ $btn }} bg-[#2A57B4] !px-3 text-white hover:bg-[#24499A]">Send link</button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($editingId === $m->id)
                            <div class="mt-3 rounded-lg bg-slate-50 p-3">
                                <label class="block text-xs font-semibold text-slate-600">Correct email address</label>
                                <input type="email" wire:model="editEmail" inputmode="email" autocomplete="off" class="{{ $field }}">
                                <p class="mt-1 text-[11px] text-slate-400">Any link already sent to the old address stops working.</p>
                                @error('send') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                                <div class="mt-3 flex gap-2">
                                    <button type="button" wire:click="saveEmail" class="{{ $btn }} flex-1 bg-[#2A57B4] text-white hover:bg-[#24499A] sm:flex-none">Save</button>
                                    <button type="button" wire:click="closeSend" class="{{ $btn }} flex-1 border border-slate-300 bg-white text-slate-700 sm:flex-none">Cancel</button>
                                </div>
                            </div>
                        @endif

                        @if ($sendingId === $m->id)
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <label class="block text-xs font-semibold text-amber-900">How did you verify this member?</label>
                                <select wire:model.live="identityMethod" class="{{ $field }} border-amber-300">
                                    <option value="">Choose…</option>
                                    @foreach (\App\Services\Workflow\TransactionWorkflow::IDENTITY_METHODS as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('identityMethod') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

                                <input type="text" wire:model="identityNote" maxlength="300" placeholder="Note (optional). No ID or phone numbers."
                                       class="{{ $field }} !mt-2 border-amber-300">

                                <label class="mt-3 flex items-start gap-3 text-sm text-amber-900">
                                    <input type="checkbox" wire:model.live="identityConfirmed" class="mt-0.5 h-5 w-5 shrink-0 accent-[#2A57B4]">
                                    <span>I confirm this person is a member of the committee and this is their email address.</span>
                                </label>
                                @error('identityConfirmed') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                                @error('send') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

                                <div class="mt-3 flex gap-2">
                                    <button type="button" wire:click="sendLink" wire:loading.attr="disabled"
                                            @disabled(! $identityMethod || ! $identityConfirmed)
                                            class="{{ $btn }} flex-[2] bg-[#2A57B4] text-white hover:bg-[#24499A] sm:flex-none">
                                        <span wire:loading.remove wire:target="sendLink">Email the link</span>
                                        <span wire:loading wire:target="sendLink">Sending…</span>
                                    </button>
                                    <button type="button" wire:click="closeSend" class="{{ $btn }} flex-1 border border-amber-300 bg-white text-slate-700 sm:flex-none">Cancel</button>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Counselling schedule --}}
    @if ($canSchedule)
        <section class="rounded-2xl border border-rose-200 bg-white p-4 shadow-sm sm:p-5">
            <h3 class="text-sm font-semibold text-slate-900">Counselling schedule</h3>
            <p class="mt-0.5 text-xs text-slate-500">The student is told to see you at this date and time. You must set it before you can sign.</p>

            @if ($notice)
                <div role="status" class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm font-medium text-emerald-800">{{ $notice }}</div>
            @endif

            @if ($slots->isNotEmpty())
                <p class="mt-3 text-xs font-medium text-slate-600">Your open slots</p>
                <div class="-mx-4 mt-1.5 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0 [&::-webkit-scrollbar]:hidden">
                    @foreach ($slots as $s)
                        <button type="button" wire:click="useSlot({{ $s->id }})" wire:key="useslot-{{ $s->id }}"
                                class="inline-flex min-h-[44px] shrink-0 items-center rounded-lg border border-slate-200 bg-slate-50 px-3 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                            {{ $s->available_date->format('M j') }}, {{ \Carbon\Carbon::createFromFormat('H:i:s', $s->start_time)->format('g:i A') }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                <div class="col-span-2 sm:col-span-1">
                    <label for="sched-date" class="text-xs font-semibold text-slate-600">Date</label>
                    <input id="sched-date" type="date" wire:model="schedDate" min="{{ now()->toDateString() }}" class="{{ $field }}">
                    @error('schedDate') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sched-start" class="text-xs font-semibold text-slate-600">From</label>
                    <input id="sched-start" type="time" wire:model="schedStart" class="{{ $field }}">
                    @error('schedStart') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sched-end" class="text-xs font-semibold text-slate-600">Until (optional)</label>
                    <input id="sched-end" type="time" wire:model="schedEnd" class="{{ $field }}">
                    @error('schedEnd') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
            @error('schedule') <p class="mt-2 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

            <button type="button" wire:click="saveSchedule" wire:loading.attr="disabled"
                    class="{{ $btn }} mt-4 w-full bg-rose-600 text-white hover:bg-rose-700 sm:w-auto">
                {{ $schedDate ? 'Update schedule' : 'Set schedule and notify student' }}
            </button>
        </section>
    @endif

    {{-- Primary actions: sticky bottom bar on phones, inline on larger screens --}}
    @if ($canAct)
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 pt-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-4px_12px_rgba(15,23,42,0.06)] backdrop-blur sm:static sm:z-auto sm:border-0 sm:bg-transparent sm:p-0 sm:shadow-none sm:backdrop-blur-none">
            <div class="mx-auto max-w-3xl">
                @if ($actionBlocked)
                    <p class="mb-2 text-xs font-medium text-amber-700">
                        {{ ! $signatureReady ? 'Add your e-signature to enable signing.' : 'Set the counselling schedule to enable signing.' }}
                    </p>
                @endif
                <div class="flex gap-2">
                    <button type="button" wire:click="openModal('reject')"
                            class="{{ $btn }} flex-1 border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 sm:flex-none">Reject</button>
                    <button type="button" wire:click="openModal('approve')" @disabled($actionBlocked)
                            class="{{ $btn }} flex-[2] bg-[#2A57B4] text-white hover:bg-[#24499A] sm:flex-none">Approve and sign</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal: bottom sheet on phones, centered dialog on larger screens --}}
    @if ($modal)
        <div class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-900/50 backdrop-blur-sm sm:items-center sm:p-4"
             wire:keydown.escape="closeModal" wire:click.self="closeModal" role="dialog" aria-modal="true">
            <div class="max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-5 pb-[max(1.25rem,env(safe-area-inset-bottom))] shadow-2xl sm:rounded-2xl sm:p-6">
                <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-slate-300 sm:hidden"></div>

                @if ($modal === 'approve')
                    <h3 class="text-lg font-bold text-slate-900">Confirm your signature</h3>
                    <p class="mt-1 text-sm text-slate-500">I have reviewed this {{ strtolower($document->name()) }} and authorize this action. Your signature below will be applied and sealed.</p>

                    @if (auth()->user()->signature_enabled)
                        @if ($signatureReady)
                            <img src="{{ route('profile.signature') }}?v={{ md5(auth()->user()->signature_path) }}" alt="Your signature" class="mt-4 h-16 rounded-lg border border-slate-100 bg-white p-1">
                        @endif
                    @else
                        <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                            Your signature image is turned off. This approval will show your name and seal only.
                        </p>
                    @endif

                    @if ($document->signerRecordsInspection())
                        <label class="mt-4 block text-sm font-medium text-slate-700">Inspection remarks <span class="text-slate-400">(optional)</span></label>
                        <textarea wire:model="inspectionRemarks" rows="3" maxlength="300" placeholder="e.g. Haircut done. Cleared." class="{{ $field }}"></textarea>
                        @error('inspectionRemarks') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    @endif

                    @if (($kind ?? null) === 'rating')
                        <p class="mt-4 text-sm font-medium text-slate-700">Your rating</p>
                        <div class="mt-1.5 space-y-1.5">
                            @foreach (\App\Workflow\Documents\ModelStudentRating::RATINGS as $n => $label)
                                <label wire:key="rt-{{ $n }}" class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-3 py-2 text-sm has-[:checked]:border-[#2A57B4] has-[:checked]:bg-blue-50">
                                    <input type="radio" wire:model="rating" value="{{ $n }}" class="h-5 w-5 accent-[#2A57B4]">
                                    <span class="font-medium text-slate-800">{{ $label }}</span><span class="ml-auto text-slate-400">({{ $n }})</span>
                                </label>
                            @endforeach
                        </div>
                        @error('rating') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    @endif

                    <label for="sign-password" class="mt-4 block text-sm font-medium text-slate-700">Enter your password to sign</label>
                    <input id="sign-password" type="password" wire:model="password" wire:keydown.enter="approve" autocomplete="current-password" class="{{ $field }}">
                    @error('password') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    @error('action') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeModal" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="approve" wire:loading.attr="disabled" class="{{ $btn }} bg-[#2A57B4] text-white hover:bg-[#24499A]">
                            <span wire:loading.remove wire:target="approve">Sign document</span>
                            <span wire:loading wire:target="approve">Signing…</span>
                        </button>
                    </div>
                @else
                    <h3 class="text-lg font-bold text-slate-900">Reject this document</h3>
                    <p class="mt-1 text-sm text-slate-500">This ends the whole transaction. The student sees your reason.</p>
                    <textarea wire:model="reason" rows="4" maxlength="500" placeholder="Why are you rejecting it?" class="{{ $field }} !mt-4"></textarea>
                    @error('action') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button type="button" wire:click="closeModal" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="reject" wire:loading.attr="disabled" class="{{ $btn }} bg-rose-600 text-white hover:bg-rose-700">Reject document</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>