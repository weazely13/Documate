<div>
    @if(!is_null($periodVerified))
        <span class="verify-indicator {{ $isVerified ? 'verified' : 'unverified' }}"
            data-tooltip="{{ $isVerified ? 'Account Verified' : 'Account Not Verified — verify to unlock full access' }}">
            <i class='bx {{ $isVerified ? 'bx-check-shield' : 'bx-error-circle' }}'></i>
            <span class="verify-label">{{ $isVerified ? 'Verified' : 'Not Verified' }}</span>
        </span>
    @endif
</div>