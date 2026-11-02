<?php

namespace App\Models;

use App\Workflow\Documents\DocumentRegistry;
use App\Workflow\Documents\WorkflowDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A report saved from the Issue page before it was issued. Only its issuer sees it. */
class IssueDraft extends Model
{
    protected $fillable = ['issuer_id', 'document_type', 'student_id', 'source_id', 'field_values'];

    protected $casts = ['field_values' => 'array'];

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function document(): ?WorkflowDocument
    {
        return DocumentRegistry::find($this->document_type);
    }

    /** Answers filled in so far, for "x of y filled" on the cards. */
    public function filledCount(): int
    {
        return collect($this->field_values ?? [])->filter(fn ($v) => $v !== null && $v !== '' && $v !== false)->count();
    }
}
