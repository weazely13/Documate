<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Feedback sent by a user, handled by the office from the admin inbox. */
class FeedbackReport extends Model
{
    use Auditable;

    protected string $auditModule = 'Feedback';
    protected array $auditIgnore = ['user_agent'];

    public const CATEGORIES = [
        'general' => 'General experience',
        'suggestion' => 'Suggestion',
        'compliment' => 'Compliment',
        'complaint' => 'Complaint',
    ];

    /**
     * Feedback has no workflow: the office reads it and may reply (a reply is optional). `status` only says whether an
     * admin has opened it yet: 'new' until then (the sidebar badge counts these), 'read' after.
     */
    public const UNREAD = 'new';
    public const READ = 'read';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'replied_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by')->withTrashed();
    }

    public function scopeUnread(Builder $q): Builder
    {
        return $q->where('status', self::UNREAD);
    }

    public function isUnread(): bool
    {
        return $this->status === self::UNREAD;
    }

    public function isReplied(): bool
    {
        return filled($this->admin_reply);
    }

    public function auditName(): string
    {
        return Str::limit($this->subject, 60);
    }

    public function reference(): string
    {
        return 'FB-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? Str::headline($this->category);
    }
}
