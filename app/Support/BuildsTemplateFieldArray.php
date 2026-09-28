<?php

namespace App\Support;

use Illuminate\Support\Str;

trait BuildsTemplateFieldArray
{
    protected function buildFieldsArray($fieldsCollection): array
    {
        $usedNames = [];

        return $fieldsCollection->map(function ($field) use (&$usedNames) {
            $baseName = $field->name ? Str::slug($field->name, '_') : ('field_' . $field->field_id);
            $name = $baseName;

            if (isset($usedNames[$name])) {
                $name = $baseName . '_' . $field->field_id;
            }
            $usedNames[$name] = true;

            return [
                'id' => (string) $field->field_id,
                'name' => $name,
                'label' => $field->label,
                'group_name' => $field->group_name ?? null,
                'type' => $field->field_type === 'paragraph' ? 'paragraph' : $field->data_type,
                'source_type' => $field->source_type,
                'system_key' => $field->system_key ?? null,
                'x' => (float) $field->x_position,
                'y' => (float) $field->y_position,
                'width' => (float) $field->width,
                'height' => (float) $field->height,
                'font_family' => $field->font_family,
                'font_weight' => $field->font_weight,
                'font_size' => (float) $field->font_size,
                'text_color' => $field->text_color,
                'alignment' => $field->alignment,
                'line_height' => (float) ($field->line_height ?? 1.3),
                'letter_spacing' => (float) ($field->letter_spacing ?? 0),
                'text_case' => $field->text_case ?? 'none',
                'date_mode' => $field->date_mode ?? 'current',
            ];
        })->values()->all();
    }
}