<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateVersion extends Model
{
    protected $table = 'template_versions';
    protected $primaryKey = 'version_id';

    public $timestamps = true;

    protected $fillable = [
        'template_id',
        'image_path',
        'document_size',
        'orientation',
        'custom_width',
        'custom_height',
        'version_number',
        'is_active',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // Parent template
    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id', 'template_id');
    }

    // Fields inside this version
    public function fields()
    {
        return $this->hasMany(TemplateField::class, 'version_id', 'version_id');
    }

    // Instructions
    public function instructions()
    {
        return $this->hasMany(TemplateInstruction::class, 'version_id', 'version_id');
    }
}
