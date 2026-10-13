@php
    $btn = 'rounded-xl px-4 py-2.5 text-sm font-semibold transition disabled:opacity-50';
    $isMine = (int) $approval->acted_by === (int) auth()->id();
@endphp

<div class="mx-auto max-w-3xl space-y-5">

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <a href="{{ route('signatory.inbox') }}" wire:navigate class="text-xs font-semibold text-slate-500 hover:text-[#2A57B4]">&larr; Signing Inbox</a>
            <h2 class="mt-1 text-2xl font-bold tracking-tight text-slate-900">{{ $document->name() }}</h2>
            <p class="font-mono text-xs text-slate-400">{{ $ws->transaction_code }}</p>
        </div>
        @include('livewire.workflow._badge', ['status' => $ws->workflow_status])
    </div>

    @error('action')
        @if (! $modal)
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</div>
        @endif
    @enderror

    @if ($canAct && ! $signatureReady)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm text-amber-900">Add your e-signature in your profile before you can approve this.</p>
            <a href="{{ route('profile') }}" wire:navigate class="rounded-lg bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-amber-700">Open profile</a>
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

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">You are signing as: {{ $approval->role->label }} ({{ $approval->label }})</h3>
        <div class="mt-4 border-t border-slate-100 pt-4">@include('livewire.workflow._preview', [
            'src' => route('signatory.preview', $approval->id),
            'version' => $ws->updated_at?->timestamp . '-' . $approvals->whereNotNull('acted_at')->count(),
        ])</div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Approval chain</h3>
        <div class="mt-3">@include('livewire.workflow._chain', ['approvals' => $approvals])</div>
    </section>
    @if (session('signing_message'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('signing_message') }}</div>
    @endif

    @if ($canSend)
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Committee members</h3>
            <p class="mt-0.5 text-xs text-slate-500">
                Send each member a secure link to review the report and sign. They confirm their email with a one-time code first.
                Verify who they are before you send.
            </p>

            @if ($notice)
                <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm font-medium text-emerald-800">{{ $notice }}</div>
            @endif

            <ul class="mt-4 space-y-3">
                @foreach ($members as $m)
                    @php
                        $req = $requests->get($m->id);
                        $live = $req && $req->status === 'pending' && $req->expires_at->isFuture();
                        $v = $m->status->value;
                    @endphp
                    <li wire:key="member-{{ $m->id }}" class="rounded-xl border border-slate-200 p-3.5">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $m->signer_name ?: $m->external_name }}</p>
                                <p class="text-xs text-slate-500">{{ $m->external_email }}</p>
                                <p class="mt-0.5 text-xs font-medium
                                    {{ $v === 'approved' ? 'text-emerald-600' : ($v === 'rejected' ? 'text-rose-600' : ($live ? 'text-blue-600' : 'text-amber-600')) }}">
                                    @if ($v === 'approved') Signed {{ $m->acted_at?->format('M j, g:i A') }}
                                    @elseif ($v === 'rejected') Declined
                                    @elseif ($live) Link sent {{ $req->created_at->diffForHumans() }}, expires {{ $req->expires_at->format('M j, g:i A') }}
                                    @else Link not sent yet
                                    @endif
                                </p>
                            </div>

                            @if ($v === 'pending')
                                @php $ready = $verified->get($m->id, false); @endphp
                                <div class="flex shrink-0 gap-2">
                                    <button type="button" wire:click="startEdit({{ $m->id }})"
                                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Fix email</button>

                                    @if ($ready)
                                        <button type="button" wire:click="resendLink({{ $m->id }})" wire:loading.attr="disabled" wire:target="resendLink({{ $m->id }})"
                                                @if ($live) wire:confirm="Email a new link? The previous link will stop working." @endif
                                                class="rounded-lg bg-[#2A57B4] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#24499A] disabled:opacity-50">
                                            <span wire:loading.remove wire:target="resendLink({{ $m->id }})">{{ $req ? 'Resend link' : 'Send link' }}</span>
                                            <span wire:loading wire:target="resendLink({{ $m->id }})">Sending…</span>
                                        </button>
                                    @else
                                        <button type="button" wire:click="openSend({{ $m->id }})"
                                                class="rounded-lg bg-[#2A57B4] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#24499A]">Send link</button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @if ($editingId === $m->id)
                            <div class="mt-3 rounded-lg bg-slate-50 p-3">
                                <label class="block text-xs font-semibold text-slate-600">Correct email address</label>
                                <input type="email" wire:model="editEmail" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <p class="mt-1 text-[11px] text-slate-400">Any link already sent to the old address stops working.</p>
                                @error('send') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                                <div class="mt-2 flex gap-2">
                                    <button type="button" wire:click="saveEmail" class="rounded-lg bg-[#2A57B4] px-3 py-1.5 text-xs font-semibold text-white">Save</button>
                                    <button type="button" wire:click="closeSend" class="text-xs text-slate-500 hover:underline">Cancel</button>
                                </div>
                            </div>
                        @endif

                        @if ($sendingId === $m->id)
                            <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3">
                                <label class="block text-xs font-semibold text-amber-900">How did you verify this member?</label>
                                <select wire:model.live="identityMethod" class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm">
                                    <option value="">Choose…</option>
                                    @foreach (\App\Services\Workflow\TransactionWorkflow::IDENTITY_METHODS as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('identityMethod') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

                                <input type="text" wire:model="identityNote" maxlength="300" placeholder="Note (optional). No ID or phone numbers."
                                    class="mt-2 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm">

                                <label class="mt-2 flex items-start gap-2 text-sm text-amber-900">
                                    <input type="checkbox" wire:model.live="identityConfirmed" class="mt-0.5 h-4 w-4 accent-[#2A57B4]">
                                    <span>I confirm this person is a member of the committee and this is their email address.</span>
                                </label>
                                @error('identityConfirmed') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror
                                @error('send') <p class="mt-1 text-xs font-medium text-rose-600">{{ $message }}</p> @enderror

                                <div class="mt-3 flex gap-2">
                                    <button type="button" wire:click="sendLink" wire:loading.attr="disabled"
                                            @disabled(! $identityMethod || ! $identityConfirmed)
                                            class="rounded-lg bg-[#2A57B4] px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-50">
                                        <span wire:loading.remove wire:target="sendLink">Email the link</span>
                                        <span wire:loading wire:target="sendLink">Sending…</span>
                                    </button>
                                    <button type="button" wire:click="closeSend" class="text-xs text-slate-500 hover:underline">Cancel</button>
                                </div>
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
    @if ($canAct)
        <div class="flex flex-wrap gap-2">
            <button type="button" wire:click="openModal('approve')" @disabled(! $signatureReady) class="{{ $btn }} bg-[#2A57B4] text-white hover:bg-[#24499A]">Approve and sign</button>
            <button type="button" wire:click="openModal('reject')" class="{{ $btn }} border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100">Reject</button>
        </div>
    @endif

    @if ($modal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" wire:keydown.escape="closeModal">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                @if ($modal === 'approve')
                    <h3 class="text-lg font-bold text-slate-900">Confirm your signature</h3>
                    <p class="mt-1 text-sm text-slate-500">I have reviewed this {{ strtolower($document->name()) }} and authorize this action. Your signature below will be applied and sealed.</p>

                    @if ($signatureReady)
                        <img src="{{ route('profile.signature') }}?v={{ md5(auth()->user()->signature_path) }}" alt="Your signature" class="mt-4 h-16 rounded-lg border border-slate-100 bg-white p-1">
                    @endif

                    <label class="mt-4 block text-sm font-medium text-slate-700">Enter your password to sign</label>
                    <input type="password" wire:model="password" wire:keydown.enter="approve" autocomplete="current-password"
                           class="mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100">
                    @error('password') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    @error('action') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" wire:click="closeModal" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="approve" wire:loading.attr="disabled" class="{{ $btn }} bg-[#2A57B4] text-white hover:bg-[#24499A]">
                            <span wire:loading.remove wire:target="approve">Sign document</span>
                            <span wire:loading wire:target="approve">Signing…</span>
                        </button>
                    </div>
                @else
                    <h3 class="text-lg font-bold text-slate-900">Reject this document</h3>
                    <p class="mt-1 text-sm text-slate-500">This ends the whole transaction. The student sees your reason.</p>
                    <textarea wire:model="reason" rows="4" maxlength="500" placeholder="Why are you rejecting it?"
                              class="mt-4 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100"></textarea>
                    @error('action') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" wire:click="closeModal" class="{{ $btn }} border border-slate-300 bg-white text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="button" wire:click="reject" wire:loading.attr="disabled" class="{{ $btn }} bg-rose-600 text-white hover:bg-rose-700">Reject document</button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
