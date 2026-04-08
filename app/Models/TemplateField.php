<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateField extends Model
{
    protected $table = 'template_fields';
    protected $primaryKey = 'field_id';

    protected $fillable = [
        'version_id',
        'label',
        'name',
        'source_type',
        'system_key',
        'data_type',
        'field_type',
        'x_position',
        'y_position',
        'width',
        'height',
        'font_family',
        'font_weight',
        'font_size',
        'text_color',
        'alignment',
        'line_height',
        'letter_spacing',
        'max_length',
        'max_lines',
        'required',
        'placeholder',
        'date_mode',
        'z_index',
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function version()
    {
        return $this->belongsTo(TemplateVersion::class, 'version_id', 'version_id');
    }
}
