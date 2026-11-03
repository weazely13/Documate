<div>
    <x-auth.shell>
        <x-slot:aside>
            <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-[13px] font-medium text-[#4a5568] hover:text-[#2a57b4]"><i class="bx bx-left-arrow-alt text-lg"></i> Back to sign in</a>
        </x-slot:aside>

        <div class="mx-auto w-full max-w-[24rem] lg:mx-0">
            <span class="grid h-11 w-11 place-items-center rounded-xl bg-[#dcecfa] text-[#2a57b4]"><i class="bx bx-key text-2xl"></i></span>
            <h1 class="mt-4 text-[1.75rem] font-semibold tracking-[-0.025em] text-[#0d253d] sm:text-[2rem]">Set a new password</h1>
            <p class="mt-1.5 text-[15px] text-[#66707f]">Use at least 8 characters. You'll sign in with it from now on.</p>

            <form wire:submit="resetPassword" class="mt-7 space-y-4">
                <div>
                    <label class="text-[13px] font-medium text-[#273951]">Account email</label>
                    <input type="email" wire:model="email" readonly class="input !bg-[#f5f4f2] !text-[#4a5568]">
                    @error('email') <p class="error">{{ $message }}</p> @enderror
                </div>
                @foreach ([['password', 'New password'], ['password_confirmation', 'Confirm new password']] as [$field, $label])
                    <div x-data="{ show: false }">
                        <label for="{{ $field }}" class="text-[13px] font-medium text-[#273951]">{{ $label }}</label>
                        <div class="relative">
                            <input id="{{ $field }}" :type="show ? 'text' : 'password'" wire:model="{{ $field }}" autocomplete="new-password" class="input pr-11 @error($field) !border-[#be123c] @enderror">
                            <button type="button" @click="show = !show" class="absolute right-1 top-1.5 grid h-11 w-10 place-items-center text-[#9a978f] hover:text-[#273951]" :aria-label="show ? 'Hide password' : 'Show password'">
                                <i class="bx text-xl" :class="show ? 'bx-hide' : 'bx-show'"></i>
                            </button>
                        </div>
                        @error($field) <p class="error">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <button type="submit" wire:loading.attr="disabled" wire:target="resetPassword"
                        class="!mt-6 flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#2a57b4] text-[15px] font-medium text-white shadow-[0_6px_16px_-6px_rgba(42,87,180,0.55)] hover:bg-[#24499a] disabled:opacity-70">
                    <i wire:loading wire:target="resetPassword" class="bx bx-loader-alt animate-spin"></i> Update password
                </button>
            </form>
        </div>
    </x-auth.shell>
</div>
