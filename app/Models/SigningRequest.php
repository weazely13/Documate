<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One emailed invitation for one external signer on one approval step. */
class SigningRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'code_expires_at' => 'datetime',
            'code_sent_at' => 'datetime',
            'verified_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(TransactionApproval::class, 'approval_id');
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(StudentDocumentWorkspace::class, 'workspace_id', 'workspace_id');
    }
}