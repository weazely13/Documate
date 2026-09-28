<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $primaryKey = 'appointment_id';

    protected $fillable = [
        'user_id', 'workspace_id', 'purpose', 'appointment_date', 'session',
        'queue_number', 'status', 'rejection_reason', 'admin_notes',
        'reschedule_reason', 'reschedule_count', 'reviewed_by', 'reviewed_at',
        'ai_flag', 'ai_findings', 'ai_reviewed_at',
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'reviewed_at' => 'datetime',
        'ai_findings' => 'array', 
        'ai_reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getAiRecommendationAttribute(): ?string
    {
        return $this->ai_findings['recommendation'] ?? null;
    }

    public function getAiIncorrectProbabilityAttribute(): ?int
    {
        return isset($this->ai_findings['incorrect_probability'])
            ? (int) $this->ai_findings['incorrect_probability']
            : null;
    }
    public function getAiReasonCategoryAttribute(): ?string
    {
        return $this->ai_findings['reason_category'] ?? null;
    }

    public function getAiReasonCategoryLabelAttribute(): ?string
    {
        return match ($this->ai_reason_category) {
            'missing_fields' => 'Missing / Incomplete Fields',
            'identity_mismatch' => 'Identity Mismatch',
            'garbled_text' => 'Garbled or Placeholder Text',
            'purpose_mismatch' => 'Purpose Mismatch',
            'invalid_format' => 'Invalid Format',
            'other' => 'Other Issues',
            default => null,
        };
    }

    public function workspace()
    {
        return $this->belongsTo(StudentDocumentWorkspace::class, 'workspace_id', 'workspace_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function sessionLabel(): string
    {
        return $this->session === 'morning' ? '8:00 AM – 12:00 PM' : '1:00 PM – 5:00 PM';
    }

    /**
     * Whether right now falls within this appointment's actual date + session window.
     * Used to gate the "it's your turn" queue message.
     */
    public function isWithinServiceWindow(): bool
    {
        if (!$this->appointment_date->isToday()) {
            return false;
        }

        $now = Carbon::now();
        [$startHour, $endHour] = $this->session === 'morning' ? [8, 12] : [13, 17];

        return $now->hour >= $startHour && $now->hour < $endHour;
    }

    public function canBeDeleted(): bool
    {
        return in_array($this->status, ['pending', 'approved']);
    }
    public function reschedules()
    {
        return $this->hasMany(AppointmentReschedule::class, 'appointment_id', 'appointment_id')->orderBy('created_at');
    }
    public function reapplications()
    {
        return $this->hasMany(AppointmentReapplication::class, 'appointment_id', 'appointment_id')->orderBy('created_at');
    }

    public function canReapply(): bool
    {
        return $this->status === 'missed';
    }
    public function refreshMissedStatusIfLapsed(): void
    {
        if ($this->status !== 'approved') {
            return;
        }

        $now = \Carbon\Carbon::now();
        [, $endHour] = $this->session === 'morning' ? [8, 12] : [13, 17];
        $sessionEnd = $this->appointment_date->copy()->setTime($endHour, 0);

        if ($this->appointment_date->lt($now->toDateString()) || $now->greaterThanOrEqualTo($sessionEnd)) {
            $this->update(['status' => 'missed']);
            $this->logStatus('missed');
            $this->workspace?->increment('missed_count');
            $this->user->notify(new \App\Notifications\AppointmentMissed($this));
        }
    }
    public function statusLogs()
    {
        return $this->hasMany(AppointmentStatusLog::class, 'appointment_id', 'appointment_id')->orderBy('created_at');
    }
    public function logStatus(string $status, ?string $note = null, ?int $changedBy = null): void
    {
        $this->statusLogs()->create([
            'status' => $status,
            'note' => $note,
            'changed_by' => $changedBy,
        ]);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Appointment pending approval',
            'approved' => 'Appointment approved',
            'rejected' => 'Appointment rejected',
            'attended' => 'Appointment visit completed',
            'missed' => 'Appointment marked as missed',
            default => 'Appointment ' . $this->status,
        };
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'approved', 'attended' => 'green',
            'rejected', 'missed' => 'red',
            'pending' => 'amber',
            default => 'default',
        };
    }
}