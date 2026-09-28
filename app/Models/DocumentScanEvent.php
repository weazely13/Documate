<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentScanEvent extends Model
{
    protected $fillable = ['workspace_id', 'status', 'message', 'uploaded_by'];

    public function workspace()
    {
        return $this->belongsTo(StudentDocumentWorkspace::class, 'workspace_id', 'workspace_id');
    }

    public function uploader()
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }

    /**
     * Always prefers the actual message (AI summary, rejection reason, error
     * text) over a generic label — this is what was missing before: the
     * overlay was showing "Rejected" instead of *why*.
     */
    public function statusLabel(): string
    {
        return match ($this->status) {
            'uploading' => $this->message ?: 'Uploading photo…',
            'queued' => $this->message ?: 'Queued for scan',
            'scanning' => $this->message ?: 'Scanning…',
            'approved' => $this->message ?: 'Verified',
            'rejected' => $this->message ?: 'Rejected',
            'failed' => $this->message ?: 'Scan failed',
            default => ucfirst($this->status),
        };
    }

    public function tone(): string
    {
        return match ($this->status) {
            'approved' => 'emerald',
            'rejected', 'failed' => 'rose',
            'scanning' => 'indigo',
            'uploading' => 'sky',
            default => 'amber', // queued
        };
    }
}