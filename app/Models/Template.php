<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Model;

class Template extends Model
{
    protected $table = 'templates';
    protected $primaryKey = 'template_id';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'category',
        'created_by',
        'status',
        'current_version_id',
    ];

    protected $casts = [
        'category' => DocumentCategory::class,
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Creator (Admin)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id')->withTrashed();
    }

    // All versions
    public function versions()
    {
        return $this->hasMany(TemplateVersion::class, 'template_id', 'template_id');
    }

    // Current active version
    public function currentVersion()
    {
        return $this->belongsTo(TemplateVersion::class, 'current_version_id', 'version_id');
    }

    public function studentWorkspaces()
    {
        return $this->hasMany(StudentDocumentWorkspace::class, 'template_id', 'template_id');
    }
}
