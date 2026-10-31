<?php

namespace App\Services\Workflow;

use App\Models\SigningRequest;
use App\Models\TransactionApproval;
use App\Notifications\ExternalSignerNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * Creates the emailed signing link for an external approval step.
 * The raw token exists only inside the email; the database keeps its SHA-256 hash, so a leaked
 * database cannot be used to sign. Inviting again revokes the previous link.
 */
class ExternalSigning
{
    public function invite(TransactionApproval $approval): SigningRequest
    {
        $approval->loadMissing('workspace');
        $ws = $approval->workspace;

        $raw = Str::random(48);
        $request = SigningRequest::create([
            'approval_id' => $approval->id,
            'workspace_id' => $ws->workspace_id,
            'token_hash' => hash('sha256', $raw),
            'email' => Str::lower(trim((string) $approval->external_email)),
            'status' => 'pending',
            'expires_at' => now()->addDays((int) config('documate.workflow.external_expiry_days', 7)),
        ]);

        try {
            Notification::route('mail', $request->email)->notify(
                ExternalSignerNotification::invite($approval, route('external.sign', $raw), $request->expires_at)
            );
        } catch (\Throwable $e) {
            $request->delete(); // the old link stays valid if this one never went out
            throw $e;
        }

        SigningRequest::where('approval_id', $approval->id)
            ->where('status', 'pending')
            ->where('id', '!=', $request->id)
            ->update(['status' => 'revoked']);

        app(TransactionWorkflow::class)->recordExternalEvent($ws, 'external.invited', [
            'to' => self::mask($request->email),
            'expires' => $request->expires_at->toDateTimeString(),
        ], $approval->id);

        return $request;
    }

    /**
     * 'rating' (Model Student Peer Rating form) or 'endorse' (adviser recommending approval).
     * Decided by document type, NOT by chain(), because chain() can change after the
     * approval was created (e.g. an adviser account gets assigned later).
     */
    public static function kindFor(TransactionApproval $approval): string
    {
        $approval->loadMissing('workspace');

        // The signer fills in the document's own fields (e.g. the ten evaluation criteria).
        if (\App\Workflow\Documents\DocumentRegistry::find($approval->workspace?->document_type)?->signerFields($approval->workspace?->field_values ?? [])) {
            return 'form';
        }

        return match ($approval->workspace?->document_type) {
            'model_student_rating', 'ymca_award_rating' => 'rating',
            'student_assistantship_recommendation' => 'recommend',
            'disciplinary_case_report' => 'review',
            'parent_consent_assistantship' => 'consent',
            'student_complaint_grievance' => str_starts_with((string) $approval->label, 'Witness') ? 'witness' : 'guardian',
            default => 'endorse',
        };
    }

    public static function findByToken(string $token): ?SigningRequest
    {
        if (strlen($token) < 40 || strlen($token) > 80) {
            return null;
        }

        return SigningRequest::where('token_hash', hash('sha256', $token))->first();
    }

    /** j***@gmail.com : safe to show on screen or write to the audit log. */
    public static function mask(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return Str::substr($local, 0, 1) . '***@' . $domain;
    }
    /** An active, set-up Signatory account that owns this email (never the student themselves). */
    public static function accountFor(?string $email, ?int $exceptUserId = null): ?User
    {
        $email = Str::lower(trim((string) $email));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('account_status', 'active')
            ->whereNotNull('email_verified_at') // invite accepted, so the account is really set up
            ->whereHas('role', fn ($q) => $q->where('role_name', 'Signatory'))
            ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->first();
    }
}