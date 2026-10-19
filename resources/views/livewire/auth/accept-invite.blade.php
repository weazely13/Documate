@php
    $input = 'mt-1.5 w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#2A57B4] focus:ring-4 focus:ring-blue-100';
@endphp

<div class="space-y-4">
    @if ($declined)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <p class="text-lg font-bold text-slate-900">Invitation declined</p>
            <p class="mt-1 text-sm text-slate-500">The VPSD office will see that you declined. You can close this page.</p>
        </section>
    @elseif (! $user)
        <section class="rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <p class="text-lg font-bold text-slate-900">This link is no longer valid</p>
            <p class="mt-1 text-sm text-slate-500">It may have expired or already been used. Ask the VPSD office to send you a new setup link.</p>
            <a href="/login" class="mt-4 inline-block text-sm font-semibold text-[#2A57B4] hover:underline">Go to sign in</a>
        </section>
    @else
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="text-xl font-bold text-slate-900">Welcome, {{ $user->first_name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                Choose a password for your DocuMate account ({{ $user->email }}).
                @if ($roles->isNotEmpty())
                    You are set up to sign as: <strong class="text-slate-700">{{ $roles->implode(', ') }}</strong>.
                @endif
            </p>

            <label class="mt-5 block text-sm font-medium text-slate-700">New password</label>
            <input type="password" wire:model="password" autocomplete="new-password" class="{{ $input }}">
            <p class="mt-1 text-xs text-slate-400">At least 8 characters, with upper and lower case letters, a number and a symbol.</p>
            @error('password') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

            <label class="mt-4 block text-sm font-medium text-slate-700">Confirm password</label>
            <input type="password" wire:model="password_confirmation" autocomplete="new-password" wire:keydown.enter="accept" class="{{ $input }}">
            <label class="mt-5 block text-sm font-medium text-slate-700">E-signature <span class="text-slate-400">(optional)</span></label>
            <p class="text-xs text-slate-400">Draw it now, or add it later from your profile.</p>
            @include('partials.signature-pad', ['model' => 'signature'])
            <label class="mt-3 flex items-start gap-2.5 text-sm text-slate-700">
                <input type="checkbox" wire:model="consentSignature" class="mt-0.5 h-4 w-4 accent-[#2A57B4]">
                <span>I consent to this signature being applied to documents I approve.</span>
            </label>
            <label class="mt-5 flex items-start gap-2.5 text-sm text-slate-700">
                <input type="checkbox" wire:model="agree" class="mt-0.5 h-4 w-4 accent-[#2A57B4]">
                <span>I agree to the Terms and Conditions and the Privacy Policy.</span>
            </label>
            @error('agree') <p class="mt-1 text-sm font-medium text-rose-600">{{ $message }}</p> @enderror

            <details class="mt-3 rounded-xl border border-slate-200 p-3 text-sm text-slate-600">
                <summary class="cursor-pointer font-medium text-[#2A57B4]">Read the Terms and Conditions</summary>
                <div class="mt-2 max-h-56 overflow-y-auto">@include('partials.terms-content')</div>
            </details>
            <details class="mt-2 rounded-xl border border-slate-200 p-3 text-sm text-slate-600">
                <summary class="cursor-pointer font-medium text-[#2A57B4]">Read the Privacy Policy</summary>
                <div class="mt-2 max-h-56 overflow-y-auto">@include('partials.privacy-content')</div>
            </details>

            <button type="button" wire:click="accept" wire:loading.attr="disabled"
                    class="mt-6 w-full rounded-xl bg-[#2A57B4] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#24499A] disabled:opacity-50">
                <span wire:loading.remove wire:target="accept">Set password and continue</span>
                <span wire:loading wire:target="accept">Saving…</span>
            </button>
            <button type="button" wire:click="decline"
                    wire:confirm="Decline this invitation? You will not be able to sign documents for the VPSD office."
                    class="mt-3 w-full text-center text-sm font-medium text-slate-500 hover:text-rose-600 hover:underline">
                I don't want this account (decline invitation)
            </button>
        </section>
    @endif
</div>