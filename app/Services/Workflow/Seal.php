<?php

namespace App\Services\Workflow;

use App\Enums\ApprovalStatus;
use App\Models\StudentDocumentWorkspace;
use App\Models\TransactionApproval;
use App\Models\TransactionAuditLog;
use Illuminate\Support\Facades\Storage;

/**
 * Everything that makes a signature checkable later:
 *  - contentHash:  fingerprint of the document's final answers
 *  - sealApproval: HMAC binding signer + role + time + auth method + signature image + document content
 *  - auditHash:    each audit row is chained to the previous one
 *  - verify:       recomputes all of it from the database and reports what still matches
 */
final class Seal
{
    public static function key(): string
    {
        return (string) (config('documate.workflow.seal_key') ?: config('app.key'));
    }

    /** Stable JSON: keys sorted recursively, so MySQL's JSON key reordering can't change the hash. */
    public static function canonical(mixed $value): string
    {
        return json_encode(self::normalize($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private static function normalize(mixed $v): mixed
    {
        if (is_array($v)) {
            if (! array_is_list($v)) {
                ksort($v);
            }

            return array_map(fn ($x) => self::normalize($x), $v);
        }

        return is_scalar($v) || $v === null ? $v : (string) $v;
    }

    public static function hmac(array $parts): string
    {
        return hash_hmac('sha256', self::canonical($parts), self::key());
    }

    public static function contentHash(StudentDocumentWorkspace $ws): string
    {
        return hash('sha256', self::canonical([
            'document_type' => (string) $ws->document_type,
            'transaction_code' => (string) $ws->transaction_code,
            'values' => $ws->field_values ?? [],
        ]));
    }

    public static function sealApproval(TransactionApproval $a): string
    {
        $ws = $a->workspace;

        $parts = [
            'approval',
            (string) $ws->transaction_code,
            (string) $ws->document_type,
            (string) $a->content_hash,
            (string) $a->step_order,
            (string) ($a->role->key ?? ''),
            (string) $a->acted_by,
            (string) $a->signer_name,
            $a->acted_at?->format('Y-m-d H:i:s') ?? '',
            (string) $a->signature_hash,
            (string) $a->auth_method,
        ];

        if (($a->signer_type ?? 'internal') === 'external') {
            $parts[] = 'external';
            $parts[] = (string) $a->external_email;
            $parts[] = $a->response ?? []; // the rating itself is sealed
        } elseif (! empty($a->response)) {
            $parts[] = 'account';
            $parts[] = $a->response;
        }

        return self::hmac($parts);
    }

    public static function auditHash(TransactionAuditLog $log, ?string $prev): string
    {
        return self::hmac([
            'audit',
            (string) $prev,
            (string) $log->workspace_id,
            (string) $log->actor_id,
            (string) $log->action,
            (string) $log->from_status,
            (string) $log->to_status,
            (string) $log->approval_id,
            $log->meta ?? [],
            $log->created_at?->format('Y-m-d H:i:s') ?? '',
        ]);
    }

    /** "A1B2-C3D4-E5F6-0718": the short code printed on the PDF. */
    public static function fingerprint(?string $contentHash): string
    {
        return rtrim(chunk_split(strtoupper(substr((string) $contentHash, 0, 16)), 4, '-'), '-');
    }

    public static function shortSeal(?string $seal): string
    {
        return strtoupper(substr((string) $seal, 0, 8));
    }

    /** Recompute everything from the database. */
    public static function verify(StudentDocumentWorkspace $ws): array
    {
        $current = self::contentHash($ws);
        $contentOk = filled($ws->content_hash) && hash_equals($ws->content_hash, $current);

        $disk = Storage::disk(SignatureStore::DISK);
        $signers = [];
        $signersOk = true;

        foreach ($ws->approvals()->with('role')->get() as $a) {
            $approved = $a->status === ApprovalStatus::Approved;

            $imageOk = true;
            if ($a->signature_hash) {
                $bytes = $a->signature_path && $disk->exists($a->signature_path) ? $disk->get($a->signature_path) : null;
                $imageOk = $bytes !== null && hash_equals($a->signature_hash, hash('sha256', $bytes));
            }

            $sealOk = $approved
                && filled($a->seal)
                && filled($a->content_hash)
                && hash_equals($a->content_hash, $current)
                && hash_equals($a->seal, self::sealApproval($a));

            $ok = $sealOk && $imageOk;
            $signersOk = $signersOk && $ok;

            $signers[] = [
                'label' => $a->label,
                'role' => $a->role->label ?? $a->label,
                'name' => $a->signer_name,
                'signed_at' => $a->acted_at,
                'seal' => self::shortSeal($a->seal),
                'ok' => $ok,
            ];
        }

        $auditOk = self::auditChainIntact($ws);

        return [
            'valid' => $contentOk && $signersOk && $auditOk,
            'content_ok' => $contentOk,
            'signers_ok' => $signersOk,
            'audit_ok' => $auditOk,
            'signers' => $signers,
            'fingerprint' => self::fingerprint($ws->content_hash),
        ];
    }
    public static function sealParty(StudentDocumentWorkspace $ws, string $kind, string $name, ?string $sigPath): string
    {
        return self::shortSeal(self::hmac([
            'party', $kind, (string) $ws->transaction_code, (string) $ws->document_type,
            self::contentHash($ws), $name,
            $sigPath ? pathinfo($sigPath, PATHINFO_FILENAME) : '',   // archive file name = sha256
            $ws->submitted_at?->format('Y-m-d H:i:s') ?? '',
        ]));
    }

    public static function auditChainIntact(StudentDocumentWorkspace $ws): bool
    {
        $prev = null;

        foreach ($ws->auditLogs()->reorder('id')->get() as $log) {
            if ($log->hash === null) {
                continue; // rows written before sealing existed
            }

            if ($log->prev_hash !== $prev || ! hash_equals($log->hash, self::auditHash($log, $prev))) {
                return false;
            }

            $prev = $log->hash;
        }

        return true;
    }
}