<?php

namespace App\Livewire\External;

use App\Enums\ApprovalStatus;
use App\Enums\TransactionStatus;
use App\Exceptions\Workflow\WorkflowException;
use App\Models\SigningRequest;
use App\Notifications\ExternalSignerNotification;
use App\Services\Workflow\ExternalSignatureStore;
use App\Services\Workflow\ExternalSigning;
use App\Services\Workflow\Seal;
use App\Services\Workflow\TransactionWorkflow;
use App\Workflow\Documents\ModelStudentRating;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use App\Workflow\Documents\DocumentRegistry;

/**
 * PUBLIC page (no login) for people without a DocuMate account.
 * email -> one-time code -> form. The link alone never opens the form: every action re-checks
 * the server-side session flag set only after the code was entered correctly.
 */
class Sign extends Component
{
    private const VERIFIED_MINUTES = 30;
    private const CODE_MINUTES = 10;
    private const MAX_CODE_ATTEMPTS = 5;

    #[Locked]
    public int $requestId;

    public string $email = '';
    public string $code = '';
    public bool $codeSent = false;

    public ?string $rating = null;
    public string $signerName = '';
    public bool $attest = false;
    public string $signature = '';

    public bool $declining = false;
    public string $declineReason = '';
    public bool $applySignature = true;

    public function mount(string $token): void
    {
        $request = ExternalSigning::findByToken($token);
        abort_unless($request, 404);

        $this->requestId = $request->id;
        $this->signerName = (string) ($request->approval?->external_name ?? '');
    }

    #[Computed]
    public function req(): SigningRequest
    {
        return SigningRequest::with(['approval', 'workspace'])->findOrFail($this->requestId);
    }

    private function sessionKey(): string
    {
        return 'external_sign.' . $this->requestId;
    }

    private function isVerified(): bool
    {
        return (int) session($this->sessionKey(), 0) > now()->subMinutes(self::VERIFIED_MINUTES)->timestamp;
    }
    private function kind(): string
    {
        return ExternalSigning::kindFor($this->req->approval);
    }
    /** done | expired | closed | email | code | form */
    private function state(): string
    {
        $r = $this->req;

        if ($r->status === 'completed') {
            return 'done';
        }
        if ($r->status !== 'pending' || $r->expires_at->isPast()) {
            return 'expired';
        }
        if ($r->approval?->status !== ApprovalStatus::Pending
            || $r->workspace->workflow_status !== TransactionStatus::UnderReview) {
            return 'closed';
        }

        return $this->isVerified() ? 'form' : ($this->codeSent ? 'code' : 'email');
    }

    private function hashCode(string $code): string
    {
        return hash_hmac('sha256', $this->requestId . '|' . $code, Seal::key());
    }

    /* ------------------------------ step 1: email ------------------------------ */

    public function sendCode(): void
    {
        $this->resetErrorBag();
        $this->validate(['email' => ['required', 'email']], [], ['email' => 'email address']);

        if (! in_array($this->state(), ['email', 'code'], true)) {
            return;
        }

        $key = 'external-code:' . $this->requestId;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('email', 'Too many codes requested. Try again in ' . ceil(RateLimiter::availableIn($key) / 60) . ' minute(s).');

            return;
        }
        RateLimiter::hit($key, 600);

        $request = $this->req;

        // Same answer whether or not the address matches, so the page reveals nothing.
        if (hash_equals($request->email, strtolower(trim($this->email)))) {
            $code = (string) random_int(100000, 999999);

            $request->forceFill([
                'code_hash' => $this->hashCode($code),
                'code_expires_at' => now()->addMinutes(self::CODE_MINUTES),
                'code_attempts' => 0,
                'code_sent_at' => now(),
            ])->save();

            try {
                Notification::route('mail', $request->email)->notify(ExternalSignerNotification::code($code, self::CODE_MINUTES));
            } catch (\Throwable $e) {
                report($e);
                $this->addError('email', 'We could not send the email right now. Please try again in a few minutes.');

                return;
            }

            app(TransactionWorkflow::class)->recordExternalEvent($request->workspace, 'external.code_sent', [
                'to' => ExternalSigning::mask($request->email),
            ], $request->approval_id);
        }

        $this->code = '';
        $this->codeSent = true;
    }

    public function changeEmail(): void
    {
        $this->codeSent = false;
        $this->code = '';
        $this->resetErrorBag();
    }

    /* ------------------------------ step 2: code ------------------------------- */

    public function verifyCode(): void
    {
        $this->resetErrorBag();
        $this->validate(['code' => ['required', 'digits:6']], ['code.digits' => 'Enter the 6-digit code.'], ['code' => 'code']);

        $request = $this->req;

        if (! $request->code_hash || ! $request->code_expires_at || $request->code_expires_at->isPast()) {
            $this->addError('code', 'That code has expired. Request a new one.');

            return;
        }

        if (hash_equals($request->code_hash, $this->hashCode($this->code))) {
            $request->forceFill(['code_hash' => null, 'code_expires_at' => null, 'code_attempts' => 0, 'verified_at' => now()])->save();
            session([$this->sessionKey() => now()->timestamp]);

            $this->dispatch('toast', type: 'success', title: 'Identity verified',
            message: 'It is now okay to review the document and sign.');
            $this->code = '';

            app(TransactionWorkflow::class)->recordExternalEvent($request->workspace, 'external.authenticated', [
                'method' => 'email_code',
                'email' => ExternalSigning::mask($request->email),
            ], $request->approval_id);

            return;
        }

        $attempts = $request->code_attempts + 1;
        $request->forceFill(['code_attempts' => $attempts])->save();

        if ($attempts >= self::MAX_CODE_ATTEMPTS) {
            $request->forceFill(['code_hash' => null, 'code_expires_at' => null])->save();
            $this->addError('code', 'Too many wrong attempts. Request a new code.');

            return;
        }

        $this->addError('code', 'That code is not correct. ' . (self::MAX_CODE_ATTEMPTS - $attempts) . ' attempt(s) left.');
    }

    /* ------------------------------ step 3: rate ------------------------------- */

    public function submit(): void
    {
        $this->resetErrorBag();

        if ($this->state() !== 'form') {
            $this->addError('action', 'Your verification expired or this link can no longer be used. Reload the page.');

            return;
        }

        $this->validate([
            'rating' => $this->kind() === 'rating' ? ['required', 'in:1,2,3,4,5'] : ['nullable'],
            'attest' => ['accepted'],
            'signerName' => ['required', 'string', 'min:3', 'max:120'],
            'signature' => $this->applySignature ? ['required', 'string', 'max:400000'] : ['nullable'],
        ], [
            'rating.required' => 'Choose a rating.',
            'attest.accepted' => 'Please confirm the statement above.',
            'signature.required' => 'Please draw your signature in the box.',
        ], ['signerName' => 'name']);

        $path = $hash = null;
        if ($this->applySignature) {
            try { [$path, $hash] = app(ExternalSignatureStore::class)->fromDataUrl($this->signature); }
            catch (InvalidArgumentException $e) { $this->addError('signature', $e->getMessage()); return; }
        }


        $isRating = $this->kind() === 'rating';
        $n = (int) $this->rating;

        $response = match ($this->kind()) {
            'rating' => ['rating' => $n, 'rating_label' => ModelStudentRating::RATINGS[$n] ?? '', 'attested' => true],
            'review' => ['reviewed' => true, 'attested' => true],
            'consent' => ['consented' => true, 'attested' => true],
            'guardian' => ['guardian_signed' => true, 'attested' => true],
            'witness' => ['witnessed' => true, 'attested' => true],
            default => ['endorsed' => true, 'attested' => true],
        };

        try {
            app(TransactionWorkflow::class)->approveExternal($this->req, $response, trim($this->signerName), $path, $hash);
        } catch (WorkflowException $e) {
            $this->addError('action', $e->getMessage());
            return;
        }

        $this->dispatch('toast', type: 'success', title: 'Response recorded',
            message: 'Thank you. Your response has been sent to the VPSD office.');

        session()->forget($this->sessionKey());
        unset($this->req);
    }

    public function decline(): void
    {
        $this->resetErrorBag();

        if ($this->state() !== 'form') {
            $this->addError('action', 'Your verification expired or this link can no longer be used. Reload the page.');

            return;
        }

        try {
            app(TransactionWorkflow::class)->declineExternal($this->req, $this->declineReason, trim($this->signerName));
        } catch (WorkflowException $e) {
            $this->addError('declineReason', $e->getMessage());

            return;
        }
        $this->dispatch('toast', type: 'success', title: 'Response recorded', message: 'Thank you. Your response has been sent to the VPSD office.');

        session()->forget($this->sessionKey());
        unset($this->req);
    }

    public function render()
    {
        $values = $this->req->workspace->field_values ?? [];
        $doc = DocumentRegistry::get($this->req->workspace->document_type);
        $keys = match ($this->kind()) {
            'review', 'guardian', 'witness' => $doc->externalOverviewFields(),
            'consent' => [],
            default => ['activity', 'purposes', 'activity_date', 'activity_time', 'venue', 'persons_involved'],
        };

        $details = collect($doc->allFields())
            ->only($keys)
            ->map(fn ($f, $k) => ['label' => $f['label'], 'value' => $doc->formatValue($f, $values[$k] ?? null)])
            ->filter(fn ($d) => ! in_array($d['value'], ['', 'No'], true))
            ->values()->all();

        return view('livewire.external.sign', [
            'state' => $this->state(),
            'student' => $values['student_name'] ?? 'the student',
            'program' => $values['student_program'] ?? '',
            'maskedEmail' => ExternalSigning::mask($this->req->email),
            'ratings' => ModelStudentRating::RATINGS,
            'kind' => $this->kind(),
            'documentName' => $doc->name(),
            'organization' => $values['non_academic_org'] ?? '',
            'details' => $details,
            'relationship' => strtolower((string) ($values['relationship'] ?? '')),
        ])->layout('layouts.external', ['title' => 'Rating request']);
    }
}