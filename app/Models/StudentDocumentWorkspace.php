<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentDocumentWorkspace extends Model
{
    protected $table = 'student_document_workspaces';
    protected $primaryKey = 'workspace_id';

    protected $fillable = [
        'user_id',
        'template_id',
        'version_id',
        'field_values',
        'generated_pdf_path',
        'last_generated_at',
        'status',
        'appointment_status',
        'appointment_date',
        'session_label',
        'supporting_file_path',
        'reference_image_path',
        'supporting_uploaded_at',
        'ocr_text',
        'completed_at',
        'processing',  
        'processing_stage',
        'analysis_status',
        'analysis_summary',
        'analysis_data',
        'rejection_reason',
        'analyzed_at',
    ];

    protected $casts = [
        'field_values' => 'array',
        'last_generated_at' => 'datetime',
        'appointment_date' => 'date',
        'completed_at' => 'datetime',
        'supporting_uploaded_at' => 'datetime',
        'analyzed_at' => 'datetime',
        'analysis_data' => 'array',
        'processing' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Hard invariant: 'completed' can only ever be reached through a saved,
        // approved VLM analysis. This blocks any other code path — now or in the
        // future — from marking a transaction done just because an appointment
        // was marked attended, or any other shortcut.
        static::saving(function (StudentDocumentWorkspace $workspace) {
            if ($workspace->isDirty('status') && $workspace->status === 'completed') {
                $eligible = filled($workspace->supporting_file_path)
                    && $workspace->analysis_status === 'approved'
                    && ! $workspace->processing;

                if (! $eligible) {
                    $workspace->status = $workspace->getOriginal('status') ?: 'pending';
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(\App\Models\Template::class, 'template_id', 'template_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function version()
    {
        return $this->belongsTo(TemplateVersion::class, 'version_id', 'version_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'workspace_id', 'workspace_id');
    }

    public function hasAttendedAppointment(): bool
    {
        return $this->appointments()->where('status', 'attended')->exists();
    }

    public function isProcessing(): bool
    {
        return (bool) $this->processing;
    }

    public function canUploadSupportingDocument(): bool
    {
        // Students no longer upload the final photo — kept only so old callers
        // that still reference it fail closed instead of erroring.
        return false;
    }

    public function needsDocumentVerification(): bool
    {
        return $this->hasAttendedAppointment()
            && $this->analysis_status !== 'approved';
    }

    public function documentVerificationStatus(): string
    {
        return match (true) {
            $this->analysis_status === 'approved' => 'Verified',
            $this->processing => 'Reviewing',
            $this->analysis_status === 'rejected' => 'Needs Re-upload',
            $this->hasAttendedAppointment() => 'Needs Upload',
            default => 'Awaiting Visit',
        };
    }

    public function isSupportingFileImage(): bool
    {
        if (! $this->supporting_file_path) {
            return false;
        }

        $ext = strtolower(pathinfo($this->supporting_file_path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function displayStatus(): array
    {
        if ($this->isCompleted()) {
            return ['label' => 'Completed', 'tone' => 'green'];
        }

        if ($this->isProcessing()) {
            return ['label' => 'Processing', 'tone' => 'amber'];
        }

        $appointment = $this->appointments()
            ->whereNotIn('status', ['retracted'])
            ->latest()
            ->first();

        if ($appointment) {
            return ['label' => $appointment->statusLabel(), 'tone' => $appointment->statusTone()];
        }

        if ($this->analysis_status === 'rejected' && $this->analyzed_at) {
            return ['label' => 'Document not accepted', 'tone' => 'red'];
        }

        return ['label' => 'Awaiting appointment', 'tone' => 'default'];
    }
}