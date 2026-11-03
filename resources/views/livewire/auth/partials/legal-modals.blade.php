{{-- Terms & Privacy readers for the sign-in page (Alpine scope: activeModal, agreeTerms, agreePrivacy) --}}
<x-legal-dialog name="terms" show="activeModal === 'terms'" check="agreeTerms" agree="activeModal = null" />
<x-legal-dialog name="privacy" show="activeModal === 'privacy'" check="agreePrivacy" agree="activeModal = null" />
