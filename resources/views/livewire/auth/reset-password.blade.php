<div class="flex min-h-screen items-center justify-center bg-white p-6">
    <form wire:submit="resetPassword" class="w-full max-w-md space-y-4">
        <h1 class="text-center text-2xl font-bold text-[#2A57B4]">Choose a new password</h1>

        <div>
            <label class="text-sm font-medium text-gray-700">Email</label>
            <input type="email" wire:model="email" class="input" readonly>
            @error('email') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">New password</label>
            <input type="password" wire:model="password" class="input" autocomplete="new-password">
            @error('password') <p class="error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700">Confirm new password</label>
            <input type="password" wire:model="password_confirmation" class="input" autocomplete="new-password">
        </div>

        <button type="submit" class="w-full rounded-full bg-[#2A57B4] py-3 font-bold text-white hover:opacity-90">Update password</button>
    </form>
</div>