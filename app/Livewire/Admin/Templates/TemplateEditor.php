<?php

namespace App\Livewire\Admin\Templates;

use Livewire\Component;
use Livewire\Attributes\Renderless;
use App\Models\Template;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;

class TemplateEditor extends Component
{
    use WithFileUploads;

    public $template;
    public $version;
    public $fields = [];
    public $selectedFieldId = null;
    public $image;
    public $systemVariables = [];
    public $templateName = '';

    protected $listeners = ['refreshFields' => '$refresh'];

    public $instructions = [];  // in-memory list of steps
    public $newInstructionText = '';  // text typed in textarea
    public $templateStatus = 'inactive';


    public function mount($template)
    {
        $this->template = Template::with('currentVersion')->findOrFail($template);
        $this->version  = $this->template->currentVersion;
        $this->templateName = $this->template->name;


        $this->systemVariables = [
            'student_number'  => 'Student Number',
            'full_name'       => 'Full Name',
            'formatted_full_name'=> 'Full Name (F. Last, Suffix)',     // 👈 new for formatted display
            'program_year_section' => 'Program + Year + Section',       // 👈 new combined one
            'first_name'      => 'First Name',
            'middle_name'     => 'Middle Name',
            'last_name'       => 'Last Name',
            'suffix'          => 'Suffix',
            'sex'             => 'Sex',
            'date_of_birth'   => 'Date of Birth',
            'email'           => 'Email',
            'contact_number'  => 'Contact Number',
            'college'         => 'College',
            'program'         => 'Program',
            'section'               => 'Section',
            'organization'    => 'Organization',
            'year_level'      => 'Year Level',
            'academic_status' => 'Academic Status',
            
        ];

        if ($this->version) {
            $this->loadFields();
            $this->loadInstructions();
            $this->templateStatus = $this->template->status; 
        }
    }
    #[Renderless]
    public function toggleStatus()
    {
        $template = Template::find($this->template->template_id);
        $newStatus = $template->status === 'active' ? 'inactive' : 'active';
        $template->update(['status' => $newStatus]);
        $this->template->status = $newStatus;
        $this->templateStatus = $newStatus;

        $this->dispatch('status-updated', status: $newStatus); // kebab-case explicitly
    }
    public function getCanvasDimensions(): array
    {
        $version = $this->version;
        $orientation = $version->orientation ?? 'portrait';

        // Pixel dimensions at 96 DPI
        $sizes = [
            'A4'     => [794,  1123],
            'A3'     => [1123, 1587],
            'Letter' => [816,  1056],
            'Legal'  => [816,  1344],
        ];

        if ($version->document_size === 'Custom' && $version->custom_width && $version->custom_height) {
            // custom_width/height assumed to be in cm — convert to px at 96dpi (1cm = 37.8px)
            $w = round($version->custom_width  * 37.8);
            $h = round($version->custom_height * 37.8);
        } else {
            [$w, $h] = $sizes[$version->document_size] ?? $sizes['A4'];
        }

        return $orientation === 'portrait'
            ? ['width' => $w, 'height' => $h]
            : ['width' => $h, 'height' => $w];
    }

    private function loadInstructions()
    {
        $dbInst = \DB::table('template_instructions')
            ->where('version_id', $this->version->version_id)
            ->orderBy('step_number')
            ->get();

        $this->instructions = $dbInst->map(fn($s) => [
            'instruction_id' => $s->instruction_id,
            'step_number'    => $s->step_number,
            'description'    => $s->description,
        ])->values()->all();
    }

    #[Renderless]
    public function addInstruction($description)
    {
        if (!$description) return;

        $nextStep = count($this->instructions) + 1;

        $id = \DB::table('template_instructions')->insertGetId([
            'version_id'  => $this->version->version_id,
            'step_number' => $nextStep,
            'description' => $description,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $newStep = [
            'instruction_id' => $id,
            'step_number'    => $nextStep,
            'description'    => $description,
        ];

        $this->instructions[] = $newStep;

        $this->dispatch('instructionAdded', step: $newStep);
    }
    #[Renderless]
    public function deleteInstruction($instructionId)
    {
        \DB::table('template_instructions')
            ->where('instruction_id', $instructionId)
            ->delete();

        $this->instructions = array_values(array_filter($this->instructions, fn ($i) => $i['instruction_id'] != $instructionId));

        $this->dispatch('instructionDeleted', id: $instructionId);
    }
    #[Renderless]
    public function reorderInstructions($orderedIds)
    {
        foreach ($orderedIds as $index => $id) {
            \DB::table('template_instructions')
                ->where('instruction_id', $id)
                ->update(['step_number' => $index + 1]);
        }

        $this->instructions = collect($this->instructions)
            ->sortBy(fn($i) => array_search($i['instruction_id'], $orderedIds))
            ->values()->all();

        $this->dispatch('instructionsReordered');
    }




    private function loadFields()
    {
        $dbFields = \DB::table('template_fields')
            ->where('version_id', $this->version->version_id)
            ->get();

        $this->fields = $dbFields->map(fn($field) => [
            'id'             => (string) $field->field_id,
            'type'           => $this->mapBackType($field->data_type, $field->field_type),
            'text'           => $field->label,
            'x'              => (float) $field->x_position,
            'y'              => (float) $field->y_position,
            'width'          => (float) $field->width,
            'height'         => (float) $field->height,
            'source_type'    => $field->source_type,
            'system_key'     => $field->system_key,
            'name'           => $field->name,
            'max_length'     => $field->max_length,
            'max_lines'      => $field->max_lines,
            'required'       => (bool) $field->required,
            'date_mode'      => $field->date_mode ?? 'current',
            'font_family'    => $field->font_family,
            'font_weight'    => $field->font_weight,
            'font_size'      => $field->font_size,
            'text_color'     => $field->text_color,
            'alignment'      => $field->alignment,
            'line_height'    => $field->line_height,
            'letter_spacing' => $field->letter_spacing,
            'placeholder'    => $field->placeholder,
        ])->values()->all();
    }

    #[Renderless]
    public function addField($type)
    {
        $id = \DB::table('template_fields')->insertGetId([
            'version_id'     => $this->version->version_id,
            'label'          => strtoupper($type),
            'name'           => 'field_' . substr(uniqid(), -8),
            'source_type'    => 'input',
            'system_key'     => null,
            'data_type'      => $this->mapDataType($type),
            'field_type'     => $type === 'paragraph' ? 'paragraph' : 'single',
            'x_position'     => 100,
            'y_position'     => 100,
            'width'          => 150,
            'height'         => 50,
            'font_family'    => 'Arial',
            'font_weight'    => 'normal',
            'font_size'      => 12,
            'text_color'     => '#000000',
            'alignment'      => 'left',
            'line_height'    => 1.4,
            'letter_spacing' => 0,
            'max_length'     => null,
            'max_lines'      => null,
            'required'       => false,
            'date_mode'      => 'current',
            'placeholder'    => null,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $newField = [
            'id'             => (string) $id,
            'type'           => $type,
            'x'              => 100,
            'y'              => 100,
            'width'          => 150,
            'height'         => 50,
            'text'           => strtoupper($type),
            'source_type'    => 'input',
            'system_key'     => null,
            'name'           => 'field_' . substr(uniqid(), -8),
            'required'       => false,
            'date_mode'      => 'current',
            'font_family'    => 'Arial',
            'font_weight'    => 'normal',
            'font_size'      => 12,
            'text_color'     => '#000000',
            'alignment'      => 'left',
            'line_height'    => 1.4,
            'letter_spacing' => 0,
            'max_length'     => null,
            'max_lines'      => null,
            'placeholder'    => null,
        ];

        // Tell the client to add this field without a re-render
        $this->dispatch('fieldAdded', field: $newField);
    }

    #[Renderless]
    public function deleteField($fieldId)
    {
        $fieldId = (string) $fieldId;

        \DB::table('template_fields')
            ->where('version_id', $this->version->version_id)
            ->where('field_id', $fieldId)
            ->delete();

        $this->dispatch('fieldDeleted', id: $fieldId);
    }

    #[Renderless]
    public function saveFields($positions, $fields, $templateName = null, $typography = [], $constraints = [])
    {
        if ($templateName !== null) {
            $this->template->update(['name' => $templateName]);
            $this->templateName = $templateName;
        }

        \DB::table('template_fields')
            ->where('version_id', $this->version->version_id)
            ->delete();

        foreach ($fields as $field) {
            $id = (string) ($field['id'] ?? '');
            if (!$id) continue;

            $pos  = $positions[$id] ?? [
                'x'      => $field['x']      ?? 100,
                'y'      => $field['y']       ?? 100,
                'width'  => $field['width']   ?? 150,
                'height' => $field['height']  ?? 50,
            ];

            $typo = $typography[$id] ?? [];
            $cons = $constraints[$id] ?? [];
            $isSystem = ($field['source_type'] ?? 'input') === 'system';
            $name = $isSystem ? ($field['system_key'] ?? $field['name'] ?? 'field_' . $id) : ($field['name'] ?? 'field_' . $id);

            \DB::table('template_fields')->insert([
                'field_id'       => (int) $id,
                'version_id'     => $this->version->version_id,
                'label'          => $field['text']        ?? 'Field',
                'name'           => $name,

                'source_type'    => $field['source_type'] ?? 'input',
                'system_key'     => $isSystem ? $name : null,
                'data_type'      => $this->mapDataType($field['type'] ?? 'text'),
                'field_type'     => ($field['type'] ?? 'text') === 'paragraph' ? 'paragraph' : 'single',
                'x_position'     => $pos['x'],
                'y_position'     => $pos['y'],
                'width'          => $pos['width'],
                'height'         => $pos['height'],
                'font_family'    => $typo['fontFamily']    ?? 'Arial',
                'font_weight'    => $typo['fontWeight']    ?? 'normal',
                'font_size'      => $typo['fontSize']      ?? 12,
                'text_color'     => $typo['color']         ?? '#000000',
                'alignment'      => $typo['textAlign']     ?? 'left',
                'line_height'    => $typo['lineHeight']    ?? 1.4,
                'letter_spacing' => $typo['letterSpacing'] ?? 0,
                'max_length'     => $cons['maxLength']     ?? null,
                'max_lines'      => $cons['maxLines']      ?? null,
                'required'       => $cons['required']      ?? false,
                'date_mode'      => $cons['dateMode']      ?? 'current',
                'placeholder'    => $field['placeholder']  ?? null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        $this->dispatch('autoSaved');
    }

    #[Renderless]
    public function publish()
    {
        $this->template->update(['status' => 'active']);
        $this->dispatch('autoSaved');
    }



    public function updatedImage()
    {
        if (!$this->image instanceof \Illuminate\Http\UploadedFile) return;

        if ($this->version->image_path) {
            \Storage::disk('public')->delete($this->version->image_path);
        }

        $path = $this->image->store('templates', 'public');
        $this->version->update(['image_path' => $path]);

        $this->dispatch('imageUploaded');  // ← changed event name

        $this->image = null;
    }

    private function mapDataType($type)
    {
        return match($type) {
            'number' => 'number',
            'date'   => 'date',
            default  => 'text',
        };
    }

    private function mapBackType($dataType, $fieldType)
    {
        if ($fieldType === 'paragraph') return 'paragraph';
        return match($dataType) {
            'number' => 'number',
            'date'   => 'date',
            default  => 'text',
        };
    }

    public function render()
    {
        return view('livewire.admin.templates.template-editor', [
            'canvasDimensions' => $this->getCanvasDimensions(),
        ])->layout('layouts.editor');
    }
}