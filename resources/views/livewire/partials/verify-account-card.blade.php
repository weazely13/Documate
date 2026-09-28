@php
    $period = \App\Models\Setting::query()->latest('id')->first();
    $isVerified = auth()->user()->isVerifiedForCurrentPeriod();
@endphp

@if($period && $isVerified === false && auth()->user()->account_status === 'active')
    <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 mb-6 flex items-start gap-3">
        <i class='bx bx-error text-yellow-500 text-2xl'></i>
        <div class="flex-1">
            <p class="font-semibold text-yellow-800">Account verification required</p>
            <p class="text-sm text-yellow-700 mt-1">
                Please verify your account by
                <strong>{{ $period->verification_end_date->format('F j, Y') }}</strong>.
                Accounts that aren't verified by the deadline will be disabled.
            </p>
        </div>
        <a href="{{ route('verify.page') }}" wire:navigate
           class="shrink-0 bg-yellow-500 text-white text-sm font-medium px-4 py-2 rounded-lg hover:bg-yellow-600">
            Verify Now
        </a>
    </div>
@endif