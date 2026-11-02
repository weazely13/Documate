<div x-data="{ activeModal: null, agreeTerms: false, agreePrivacy: false }" @keydown.escape.window="activeModal = null">
    <x-auth.shell>
        <x-slot:aside>
            <a href="{{ route('document.lookup') }}" class="hidden items-center gap-1.5 text-[13px] font-medium text-[#4a5568] hover:text-[#2a57b4] sm:inline-flex">
                <i class="bx bx-shield-quarter text-base text-[#2a57b4]" aria-hidden="true"></i> Verify a document
            </a>
        </x-slot:aside>

        <div class="mx-auto w-full max-w-[24rem] lg:mx-0">
            <h1 class="text-[1.75rem] font-semibold tracking-[-0.025em] text-[#0d253d] sm:text-[2rem]">Sign in</h1>
            <p class="mt-1.5 text-[15px] text-[#66707f]">Welcome back. Use your student number or the email on your account.</p>

            @if (session('status'))
                <div class="mt-5 flex gap-2.5 rounded-lg bg-[#d9f3e1] px-3.5 py-3 text-sm text-[#065f46]" role="status">
                    <i class="bx bx-check-circle mt-0.5 text-base"></i><span>{{ session('status') }}</span>
                </div>
            @endif

            <form wire:submit.prevent="loginUser" class="mt-7 space-y-4">
                <div>
                    <label for="login" class="text-[13px] font-medium text-[#273951]">Student number or email</label>
                    <input id="login" wire:model="login" autocomplete="username" autofocus placeholder="e.g. 2100273 or juan@gmail.com"
                           class="input @error('login') !border-[#be123c] @enderror">
                    @error('login') <p class="error">{{ $message }}</p> @enderror
                    @if ($canRecover)
                        <div class="mt-2 rounded-lg bg-[#fef3c7] px-3 py-2.5 text-[13px] text-[#78350f]">
                            Your account was removed, but it can be restored.
                            <button type="button" wire:click="sendRecovery" wire:loading.attr="disabled" wire:target="sendRecovery" class="font-medium text-[#1e4090] underline">
                                <span wire:loading.remove wire:target="sendRecovery">Email me a recovery link</span>
                                <span wire:loading wire:target="sendRecovery">Sending…</span>
                            </button>
                        </div>
                    @endif
                </div>

                <div x-data="{ show: false }">
                    <div class="flex items-center justify-between">
                        <label for="password" class="text-[13px] font-medium text-[#273951]">Password</label>
                        <button type="button" wire:click="openForgot" class="text-[13px] font-medium text-[#2a57b4] hover:underline underline-offset-4">Forgot password?</button>
                    </div>
                    <div class="relative">
                        <input id="password" :type="show ? 'text' : 'password'" wire:model="password" autocomplete="current-password" placeholder="Your password"
                               class="input pr-11 @error('password') !border-[#be123c] @enderror">
                        <button type="button" @click="show = !show" class="absolute right-1 top-1.5 grid h-11 w-10 place-items-center text-[#9a978f] hover:text-[#273951]" :aria-label="show ? 'Hide password' : 'Show password'">
                            <i class="bx text-xl" :class="show ? 'bx-hide' : 'bx-show'" aria-hidden="true"></i>
                        </button>
                    </div>
                    @error('password') <p class="error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="loginUser"
                        class="!mt-6 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#2a57b4] text-[15px] font-medium text-white shadow-[0_6px_16px_-6px_rgba(42,87,180,0.55)] transition hover:bg-[#24499a] active:bg-[#1e4090] disabled:opacity-70">
                    <i wire:loading wire:target="loginUser" class="bx bx-loader-alt animate-spin"></i>
                    <span>Sign in</span>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-[#4a5568]">
                New to DocuMate? <a href="{{ route('register') }}" class="font-medium text-[#2a57b4] hover:underline underline-offset-4">Create a student account</a>
            </p>

            <p class="mt-8 text-center text-xs leading-relaxed text-[#9a978f]">
                By signing in you agree to the
                <button type="button" @click="activeModal = 'terms'" class="font-medium text-[#4a5568] underline decoration-[#d4d1cb] underline-offset-2 hover:text-[#2a57b4]">Terms &amp; Conditions</button>
                and the
                <button type="button" @click="activeModal = 'privacy'" class="font-medium text-[#4a5568] underline decoration-[#d4d1cb] underline-offset-2 hover:text-[#2a57b4]">Privacy Policy</button>.
            </p>
        </div>
    </x-auth.shell>

    {{-- ===== Forgot password ===== --}}
    @if ($showForgot)
        <div class="fixed inset-0 z-[100] flex items-end justify-center sm:items-center sm:px-4" role="dialog" aria-modal="true" aria-labelledby="forgot-title">
            <div class="absolute inset-0 bg-[#0d253d]/45 backdrop-blur-[2px]" wire:click="closeForgot"></div>
            <div class="relative max-h-[92vh] w-full max-w-md overflow-y-auto rounded-t-2xl bg-white p-6 shadow-2xl sm:rounded-xl sm:p-7">
                <button type="button" wire:click="closeForgot" class="absolute right-3 top-3 grid h-8 w-8 place-items-center rounded-lg text-[#9a978f] hover:bg-[#f5f4f2] hover:text-[#273951]" aria-label="Close"><i class="bx bx-x text-xl"></i></button>

                @if ($forgotStep === 'ask')
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#dcecfa] text-[#2a57b4]"><i class="bx bx-lock-open-alt text-2xl"></i></span>
                    <h2 id="forgot-title" class="mt-4 text-xl font-semibold tracking-[-0.015em] text-[#0d253d]">Reset your password</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-[#4a5568]">Enter the email on your account and either your last name or your ID number. We'll send the link only to the email saved on your account.</p>

                    <form wire:submit="sendResetLink" class="mt-5 space-y-3.5">
                        <div>
                            <label for="resetEmail" class="text-[13px] font-medium text-[#273951]">Email on your account</label>
                            <input id="resetEmail" type="email" wire:model="resetEmail" autocomplete="email" placeholder="you@gmail.com"
                                   class="input @error('resetEmail') !border-[#be123c] @enderror">
                            @error('resetEmail') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div class="rounded-lg border border-[#e7e5e1] bg-[#fafaf9] p-3.5">
                            <p class="text-[13px] font-medium text-[#273951]">And one of these</p>
                            <div class="mt-2 grid grid-cols-1 items-start gap-2 sm:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)]">
                                <div>
                                    <label for="resetLastName" class="sr-only">Last name</label>
                                    <input id="resetLastName" wire:model="resetLastName" autocomplete="family-name" placeholder="Last name"
                                           class="input !mt-0 @error('resetLastName') !border-[#be123c] @enderror">
                                </div>
                                <span class="self-center text-center text-xs text-[#9a978f]">or</span>
                                <div>
                                    <label for="resetStudentNumber" class="sr-only">Student or ID number</label>
                                    <input id="resetStudentNumber" wire:model="resetStudentNumber" autocomplete="off" placeholder="Student / ID no."
                                           class="input !mt-0 @error('resetStudentNumber') !border-[#be123c] @enderror">
                                </div>
                            </div>
                            @error('resetLastName') <p class="error">{{ $message }}</p> @enderror
                            @error('resetStudentNumber') <p class="error">{{ $message }}</p> @enderror
                            @if (! $errors->has('resetLastName') && ! $errors->has('resetStudentNumber'))
                                <p class="mt-1.5 text-xs text-[#9a978f]">Signatories without an ID number can use their last name.</p>
                            @endif
                        </div>
                        <button type="submit" wire:loading.attr="disabled" wire:target="sendResetLink"
                                class="!mt-5 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#2a57b4] text-[15px] font-medium text-white hover:bg-[#24499a] disabled:opacity-70">
                            <i wire:loading wire:target="sendResetLink" class="bx bx-loader-alt animate-spin"></i> Send reset link
                        </button>
                    </form>
                    <button type="button" wire:click="closeForgot" class="mt-3 flex w-full items-center justify-center gap-1 text-sm font-medium text-[#4a5568] hover:text-[#2a57b4]"><i class="bx bx-left-arrow-alt text-lg"></i> Back to sign in</button>

                @elseif ($forgotStep === 'mismatch')
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#fef3c7] text-[#b45309]"><i class="bx bx-envelope text-2xl"></i></span>
                    <h2 id="forgot-title" class="mt-4 text-xl font-semibold tracking-[-0.015em] text-[#0d253d]">That email isn't on your account</h2>

                    @if ($hintEmail)
                        <p class="mt-1.5 text-sm leading-relaxed text-[#4a5568]">
                            Your name and ID number match an account, but it doesn't use <span class="font-medium text-[#0d253d]">{{ $resetEmail }}</span>, so nothing was sent there.
                        </p>
                        <div class="mt-5 rounded-lg bg-[#f0f4fb] p-4 ring-1 ring-[#d3dcee]">
                            <p class="text-[13px] text-[#4a5568]">Your account uses</p>
                            <p class="mt-0.5 break-all font-mono text-base font-semibold tracking-wide text-[#0d253d]">{{ $hintEmail }}</p>
                            <button type="button" wire:click="sendToHint" wire:loading.attr="disabled" wire:target="sendToHint"
                                    class="mt-3.5 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#2a57b4] text-sm font-medium text-white hover:bg-[#24499a] disabled:opacity-70">
                                <i wire:loading wire:target="sendToHint" class="bx bx-loader-alt animate-spin"></i> Send the reset link to this email
                            </button>
                        </div>
                        <p class="mt-3 text-xs leading-relaxed text-[#66707f]">Can't open that inbox anymore? Visit the VPSD office (Administration Building, 2nd Floor) with your school ID to change the email on your account.</p>
                    @else
                        <p class="mt-1.5 text-sm leading-relaxed text-[#4a5568]">
                            No account matches <span class="font-medium text-[#0d253d]">{{ $resetEmail }}</span> with that name, so nothing was sent.
                            Check the spelling, or use your student / ID number instead and we'll show you which email your account uses.
                        </p>
                    @endif

                    @error('resetEmail') <p class="error">{{ $message }}</p> @enderror
                    <button type="button" wire:click="$set('forgotStep', 'ask')" class="mt-5 flex w-full items-center justify-center gap-1 text-sm font-medium text-[#4a5568] hover:text-[#2a57b4]"><i class="bx bx-left-arrow-alt text-lg"></i> Change my details</button>

                @else
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#d9f3e1] text-[#059669]"><i class="bx bx-mail-send text-2xl"></i></span>
                    <h2 id="forgot-title" class="mt-4 text-xl font-semibold tracking-[-0.015em] text-[#0d253d]">Check your email</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-[#4a5568]">
                        We sent a reset link to <span class="break-all font-mono font-semibold text-[#0d253d]">{{ $sentTo }}</span>, the email on your account.
                        It works for 60 minutes. Check your spam folder if it isn't there in a minute.
                    </p>
                    <button type="button" wire:click="closeForgot" class="mt-6 flex h-11 w-full items-center justify-center rounded-lg bg-[#2a57b4] text-[15px] font-medium text-white hover:bg-[#24499a]">Back to sign in</button>
                @endif
            </div>
        </div>
    @endif

    @include('livewire.auth.partials.legal-modals')
</div>
