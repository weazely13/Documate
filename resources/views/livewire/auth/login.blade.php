{{--
    CHANGES MADE ON THIS PAGE:
    1. Logo font: already set to font-family: 'Gveret Levin', cursive (same as the
       landing page's wordmark). Make sure the layout this view renders inside also
       loads the font, e.g. in the shared <head>:

           <link rel="preconnect" href="https://fonts.googleapis.com">
           <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
           <link href="https://fonts.googleapis.com/css2?family=Gveret+Levin&display=swap" rel="stylesheet">

       If that link isn't already in your layout, the browser falls back to a
       generic cursive font instead of matching the landing page.

    2. "Keep me signed in" checkbox removed.

    3. Footer "Terms" / "Policy" links now open modals (same structure as the
       landing page's modals) instead of navigating to /terms and /policy.
--}}

<div class="min-h-screen grid grid-cols-1 md:grid-cols-4 bg-white"
     x-data="{ activeModal: null, agreeTerms: false, agreePrivacy: false }"
     @keydown.escape.window="activeModal = null">

    <!-- LEFT SIDE -->
    <div class="md:col-span-3 flex items-center justify-center p-10 bg-white">

        <div class="w-full max-w-md">

            <!-- LOGO -->
            <div class="flex flex-col items-center gap-2 mb-6">
                <img src="/images/favicon.png" class="w-14 h-14 object-contain" alt="DocuMate Logo">
            </div>

            <!-- TITLE -->
            <h1 class="text-2xl font-semibold tracking-tight text-center mb-1 text-slate-900">
                Login to your account
            </h1>
            <p class="text-sm text-gray-500 text-center mb-6">
                Welcome back — sign in to continue to DocuMate.
            </p>
            @if (session('status'))
                <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
            @endif
            <form wire:submit.prevent="loginUser" class="space-y-4">

                <!-- LOGIN -->
                <div>
                    <label class="text-sm font-medium text-gray-700">Student Number or Email <span class="text-red-500">*</span></label>

                    <input
                        wire:model.live="login"
                        placeholder="e.g. 202312345 or juan@gmail.com"
                        class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                        @error('login') border-red-500 @enderror
                        @if($login && !$errors->has('login')) border-green-500 @endif">

                    @error('login')
                        <p class="error">{{ $message }}</p>
                    @enderror
                    @if ($canRecover)
                        <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-900">
                            Your account was removed, but it can be restored.
                            <button type="button" wire:click="sendRecovery" wire:loading.attr="disabled" wire:target="sendRecovery"
                                    class="font-semibold text-[#2A57B4] underline">
                                <span wire:loading.remove wire:target="sendRecovery">Email me a recovery link</span>
                                <span wire:loading wire:target="sendRecovery">Sending…</span>
                            </button>
                        </div>
                    @endif
                </div>


                <!-- PASSWORD -->
                <div x-data="{ show:false }">
                    <label class="text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>

                    <div class="relative">
                        <input
                            :type="show ? 'text' : 'password'"
                            wire:model.live="password"
                            placeholder="Enter your password"
                            class="input pr-10 focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30
                            @error('password') border-red-500 @enderror
                            @if($password && !$errors->has('password')) border-green-500 @endif">

                        <!-- EYE ICON -->
                        <button type="button"
                            @click="show = !show"
                            class="absolute right-3 top-3 text-gray-500 hover:text-[#2A57B4] transition">

                            <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592M6.1 6.1A9.956 9.956 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.956 9.956 0 01-4.043 5.207M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3v6" />
                            </svg>

                        </button>
                    </div>

                    @error('password')
                        <p class="error">{{ $message }}</p>
                    @enderror
                    <div class="mt-1.5 text-right">
                        <button type="button" wire:click="$set('showForgot', true)" class="text-xs font-medium text-[#2A57B4] hover:underline">
                            Forgot password?
                        </button>
                    </div>
                </div>

                {{-- "Keep me signed in" checkbox removed per request --}}

                <!-- LOGIN BUTTON -->
                <button
                    type="submit"
                    class="w-full h-11 bg-[#2A57B4] hover:bg-[#24499A] active:bg-[#1E4090] text-white font-medium rounded-lg shadow-sm transition-colors">
                    Sign In
                </button>

            </form>

            <!-- DIVIDER -->
            <div class="flex items-center my-6">
                <div class="flex-grow h-px bg-gray-200"></div>
                <span class="mx-3 text-gray-400 text-sm">or</span>
                <div class="flex-grow h-px bg-gray-200"></div>
            </div>

            <!-- REGISTER BUTTON -->
            <a href="/register"
               class="flex h-11 w-full items-center justify-center bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-800 font-medium rounded-lg shadow-sm transition-colors">
                Create an account
            </a>

            <!-- TERMS -->
            <p class="mt-6 text-xs text-gray-500 text-center">
                By signing in you agree to our
                <button type="button" @click="activeModal = 'terms'" class="text-[#2A57B4] hover:underline font-medium">Terms</button>
                and
                <button type="button" @click="activeModal = 'privacy'" class="text-[#2A57B4] hover:underline font-medium">Policy</button>.
            </p>

        </div>
    </div>


    <!-- RIGHT SIDE IMAGE -->
    <div class="hidden md:flex md:col-span-1 relative bg-cover bg-center items-end"
         style="background-image: url('/images/backdrop.jpg');">
        <div class="absolute inset-0 bg-gradient-to-t from-[#2A57B4]/90 via-[#2A57B4]/40 to-transparent"></div>
        <div class="relative z-10 p-8 text-white">
            <p class="text-2xl font-bold leading-snug">
                Tap, select, access.
            </p>
            <p class="text-sm text-white/80 mt-2">
                Transparent, streamlined student transactions.
            </p>
        </div>
    </div>

    <!-- ===== TERMS MODAL ===== -->
    <div x-show="activeModal === 'terms'" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-8" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="activeModal = null"></div>
        <div class="relative bg-white rounded-2xl max-w-2xl w-full shadow-2xl flex flex-col max-h-[85vh]">

            <div class="flex items-start justify-between px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-gray-100">
                <div>
                    <p class="text-[.7rem] uppercase tracking-wider text-[#2A57B4] font-bold">VPSD DocuMate — Leyte Normal University</p>
                    <h3 class="text-2xl font-semibold tracking-tight text-slate-900 mt-1">Terms & Conditions</h3>
                    <p class="text-xs text-gray-400 mt-1">Effective Date: September 28, 2026</p>
                </div>
                <button type="button" @click="activeModal = null" class="text-gray-400 hover:text-gray-700 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 md:px-8 py-5 overflow-y-auto text-sm text-gray-600">
                {{-- Pull this from the same partial used on the landing page and
                     register page so all three stay word-for-word identical:
                     @include('partials.terms-content') --}}
                @include('partials.terms-content')
            </div>

            <div class="px-6 md:px-8 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer select-none">
                    <input type="checkbox" x-model="agreeTerms" class="mt-0.5 w-4 h-4 accent-[#2A57B4] shrink-0">
                    <span>I have read and agree to the Terms and Conditions.</span>
                </label>
                <button type="button" :disabled="!agreeTerms" @click="activeModal = null"
                    class="px-6 py-2 bg-[#2A57B4] text-white rounded-lg font-medium transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed enabled:hover:opacity-90">
                    I Agree
                </button>
            </div>
        </div>
    </div>

    <!-- ===== PRIVACY MODAL ===== -->
    <div x-show="activeModal === 'privacy'" x-cloak class="fixed inset-0 z-[100] flex items-center justify-center px-4 py-8" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="activeModal = null"></div>
        <div class="relative bg-white rounded-2xl max-w-2xl w-full shadow-2xl flex flex-col max-h-[85vh]">

            <div class="flex items-start justify-between px-6 md:px-8 pt-6 md:pt-8 pb-4 border-b border-gray-100">
                <div>
                    <p class="text-[.7rem] uppercase tracking-wider text-[#2A57B4] font-bold">VPSD DocuMate — Leyte Normal University</p>
                    <h3 class="text-2xl font-semibold tracking-tight text-slate-900 mt-1">Privacy Policy</h3>
                    <p class="text-xs text-gray-400 mt-1">Effective Date: September 28, 2026</p>
                </div>
                <button type="button" @click="activeModal = null" class="text-gray-400 hover:text-gray-700 transition shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/>
                    </svg>
                </button>
            </div>

            <div class="px-6 md:px-8 py-5 overflow-y-auto text-sm text-gray-600">
                @include('partials.privacy-content')
            </div>

            <div class="px-6 md:px-8 py-4 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <label class="flex items-start gap-2 text-sm text-gray-600 cursor-pointer select-none">
                    <input type="checkbox" x-model="agreePrivacy" class="mt-0.5 w-4 h-4 accent-[#2A57B4] shrink-0">
                    <span>I have read and agree to the Privacy Policy.</span>
                </label>
                <button type="button" :disabled="!agreePrivacy" @click="activeModal = null"
                    class="px-6 py-2 bg-[#2A57B4] text-white rounded-lg font-medium transition shrink-0 disabled:opacity-40 disabled:cursor-not-allowed enabled:hover:opacity-90">
                    I Agree
                </button>
            </div>
        </div>
    </div>
        @if ($showForgot)
        <div class="fixed inset-0 z-[100] flex items-center justify-center px-4" role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/50" wire:click="$set('showForgot', false)"></div>
            <form wire:submit="sendResetLink" class="relative w-full max-w-sm space-y-4 rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold tracking-tight text-slate-900">Reset your password</h3>
                <p class="text-sm text-gray-500">Enter the email on your account and we'll send you a reset link.</p>
                <div>
                    <input type="email" wire:model="resetEmail" placeholder="you@gmail.com" autofocus
                        class="input focus:border-[#2A57B4] focus:ring-2 focus:ring-[#2A57B4]/30 @error('resetEmail') border-red-500 @enderror">
                    @error('resetEmail') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" wire:click="$set('showForgot', false)" class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-[#2A57B4] px-5 py-2 text-sm font-medium text-white hover:bg-[#24499A]">Send link</button>
                </div>
            </form>
        </div>
    @endif

</div>