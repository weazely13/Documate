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
    ];

    protected $casts = [
        'field_values' => 'array',
        'last_generated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id', 'template_id');
    }

    public function version()
    {
        return $this->belongsTo(TemplateVersion::class, 'version_id', 'version_id');
    }
}
