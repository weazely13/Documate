<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemplateSignatoryStep extends Model
{
    protected $fillable = ['template_id', 'step_order', 'signatory_role_id', 'label'];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class, 'template_id', 'template_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(SignatoryRole::class, 'signatory_role_id');
    }
}
