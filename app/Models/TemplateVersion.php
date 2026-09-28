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
    public function canvasDimensions(): array
    {
        $sizes = [
            'A4' => [794, 1123], 'A3' => [1123, 1587],
            'Letter' => [816, 1056], 'Legal' => [816, 1344],
        ];

        if ($this->document_size === 'Custom' && $this->custom_width && $this->custom_height) {
            $width = round($this->custom_width * 37.8);
            $height = round($this->custom_height * 37.8);
        } else {
            [$width, $height] = $sizes[$this->document_size] ?? $sizes['A4'];
        }

        return ($this->orientation ?? 'portrait') === 'landscape'
            ? ['width' => $height, 'height' => $width]
            : ['width' => $width, 'height' => $height];
    }
}
