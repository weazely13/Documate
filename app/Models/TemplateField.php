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
        'text_case',        // ← add this
        'max_length',
        'max_lines',
        'required',
        'placeholder',
        'date_mode',
        'z_index',
        'group_name',        // ← you're also saving this per the editor JS — check it's here too
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function version()
    {
        return $this->belongsTo(TemplateVersion::class, 'version_id', 'version_id');
    }
}
