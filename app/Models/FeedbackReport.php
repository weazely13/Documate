<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Feedback or an issue report sent by a user, handled by the office from the admin inbox. */
class FeedbackReport extends Model
{
    use Auditable;

    protected string $auditModule = 'Feedback & Issues';
    protected array $auditIgnore = ['user_agent'];

    public const TYPES = [
        'feedback' => 'Feedback',
        'issue' => 'Issue report',
    ];

    public const CATEGORIES = [
        'feedback' => [
            'general' => 'General experience',
            'suggestion' => 'Suggestion',
            'compliment' => 'Compliment',
            'complaint' => 'Complaint',
        ],
        'issue' => [
            'bug' => 'Something is broken',
            'transaction' => 'Transactions & documents',
            'appointment' => 'Appointments',
            'account' => 'Account & sign-in',
            'other' => 'Something else',
        ],
    ];

    public const SEVERITIES = [
        'low' => 'Minor: I can still do what I need',
        'medium' => 'Moderate: it slows me down',
        'high' => 'Blocking: I cannot continue at all',
    ];

    public const STATUSES = [
        'new' => 'New',
        'in_progress' => 'In progress',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ];

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

    public function scopeOpen(Builder $q): Builder
    {
        return $q->whereIn('status', ['new', 'in_progress']);
    }

    public function auditName(): string
    {
        return Str::limit($this->subject, 60);
    }

    public function reference(): string
    {
        return ($this->type === 'issue' ? 'ISS-' : 'FB-') . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? Str::headline($this->type);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->type][$this->category] ?? Str::headline($this->category);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? Str::headline($this->status);
    }

    public function severityLabel(): ?string
    {
        return $this->severity ? Str::before(self::SEVERITIES[$this->severity] ?? $this->severity, ':') : null;
    }

    /** Tailwind classes for the status pill, shared by the user and admin pages. */
    public static function statusBadge(string $status): string
    {
        return match ($status) {
            'new' => 'bg-blue-50 text-[#2A57B4] ring-blue-200',
            'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'resolved' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            default => 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    }
}
