<?php

namespace App\Livewire\Admin\Templates;

use App\Models\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Admin › Templates › a template. The canvas is run in the browser (resources/js/template-editor.js);
 * this component loads the template and stores what the editor sends.
 */
class TemplateEditor extends Component
{
    use WithFileUploads;

    public $template;
    public $version;
    public $image;
    public array $instructions = [];

    /** Paper sizes in px at 96 DPI, portrait. */
    private const SIZES = [
        'A4' => [794, 1123],
        'A3' => [1123, 1587],
        'Letter' => [816, 1056],
        'Legal' => [816, 1344],
    ];

    /** What a field can be filled from, besides the student's own answer. */
    public const SYSTEM_VARIABLES = [
        'student_number' => 'Student number',
        'full_name' => 'Full name',
        'formatted_full_name' => 'Full name (F. Last, Suffix)',
        'program_year_section' => 'Program, year and section',
        'first_name' => 'First name',
        'middle_name' => 'Middle name',
        'last_name' => 'Last name',
        'suffix' => 'Suffix',
        'sex' => 'Sex',
        'date_of_birth' => 'Date of birth',
        'email' => 'Email',
        'contact_number' => 'Contact number',
        'college' => 'College',
        'program' => 'Program',
        'section' => 'Section',
        'organization' => 'Organization',
        'year_level' => 'Year level',
        'academic_status' => 'Academic status',
        'current_semester' => 'Current semester (automatic)',
        'current_academic_year' => 'Current school year (automatic)',
    ];

    public function mount($template)
    {
        $this->template = Template::with('currentVersion')->findOrFail($template);
        $this->version = $this->template->currentVersion;
        abort_unless($this->version, 404);
        $this->loadInstructions();
    }

    // ── fields ──────────────────────────────────────────────────────────────

    private function loadFields(): array
    {
        return DB::table('template_fields')
            ->where('version_id', $this->version->version_id)
            ->orderBy('field_id')
            ->get()
            ->map(fn ($f) => [
                'id' => (string) $f->field_id,
                'type' => $this->mapBackType($f->data_type, $f->field_type),
                'text' => $f->label,
                'group_name' => $f->group_name,
                'x' => (float) $f->x_position,
                'y' => (float) $f->y_position,
                'width' => (float) $f->width,
                'height' => (float) $f->height,
                'source_type' => $f->source_type ?: 'input',
                'system_key' => $f->system_key,
                'name' => $f->name,
                'max_length' => $f->max_length,
                'max_lines' => $f->max_lines,
                'required' => (bool) $f->required,
                'date_mode' => $f->date_mode ?? 'current',
                'font_family' => $f->font_family ?: 'Arial',
                'font_weight' => $f->font_weight ?: 'normal',
                'font_size' => (float) ($f->font_size ?: 12),
                'text_color' => $f->text_color ?: '#000000',
                'alignment' => $f->alignment ?: 'left',
                'line_height' => (float) ($f->line_height ?: 1.4),
                'letter_spacing' => (float) ($f->letter_spacing ?? 0),
                'text_case' => $f->text_case ?? 'none',
                'placeholder' => $f->placeholder,
            ])
            ->values()
            ->all();
    }

    /**
     * Stores the editor's whole field list (its order is the stacking order) and the template's name.
     * Fields made in the browser come with "tmp-…" ids; the real ids are returned so the editor can adopt them.
     *
     * @return array<string, int> tmp id => real id
     */
    #[Renderless]
    public function saveFields(array $fields, ?string $name = null): array
    {
        $name = trim((string) $name);
        if ($name !== '' && $name !== $this->template->name) {
            $this->template->update(['name' => mb_substr($name, 0, 255)]);
        }

        $map = [];
        $types = ['text', 'number', 'paragraph', 'date'];

        DB::transaction(function () use ($fields, $types, &$map) {
            DB::table('template_fields')->where('version_id', $this->version->version_id)->delete();

            foreach (array_slice($fields, 0, 500) as $f) {
                $type = in_array($f['type'] ?? null, $types, true) ? $f['type'] : 'text';
                $isSystem = ($f['source_type'] ?? 'input') === 'system' && array_key_exists((string) ($f['system_key'] ?? ''), self::SYSTEM_VARIABLES);
                $varName = $isSystem ? $f['system_key'] : (preg_replace('/[^A-Za-z0-9_]/', '_', (string) ($f['name'] ?? '')) ?: 'field');
                $num = fn ($v, $default, $min = null, $max = null) => is_numeric($v)
                    ? max($min ?? -INF, min($max ?? INF, (float) $v))
                    : $default;

                $row = [
                    'version_id' => $this->version->version_id,
                    'label' => mb_substr(trim((string) ($f['text'] ?? '')) ?: ucfirst($type), 0, 255),
                    'name' => mb_substr($varName, 0, 120),
                    'group_name' => ($g = trim((string) ($f['group_name'] ?? ''))) !== '' ? mb_substr($g, 0, 120) : null,
                    'source_type' => $isSystem ? 'system' : 'input',
                    'system_key' => $isSystem ? $f['system_key'] : null,
                    'data_type' => $this->mapDataType($type),
                    'field_type' => $type === 'paragraph' ? 'paragraph' : 'single',
                    'x_position' => round($num($f['x'] ?? null, 0), 2),
                    'y_position' => round($num($f['y'] ?? null, 0), 2),
                    'width' => round($num($f['width'] ?? null, 150, 4), 2),
                    'height' => round($num($f['height'] ?? null, 30, 4), 2),
                    'font_family' => in_array($f['font_family'] ?? null, ['Arial', 'Times New Roman', 'Georgia', 'Courier New'], true) ? $f['font_family'] : 'Arial',
                    'font_weight' => in_array($f['font_weight'] ?? null, ['normal', 'bold', '100', 'italic'], true) ? $f['font_weight'] : 'normal',
                    'font_size' => $num($f['font_size'] ?? null, 12, 4, 200),
                    'text_color' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($f['text_color'] ?? '')) ? $f['text_color'] : '#000000',
                    'alignment' => in_array($f['alignment'] ?? null, ['left', 'center', 'right'], true) ? $f['alignment'] : 'left',
                    'line_height' => $num($f['line_height'] ?? null, 1.4, 0.5, 5),
                    'letter_spacing' => $num($f['letter_spacing'] ?? null, 0, -10, 40),
                    'text_case' => in_array($f['text_case'] ?? null, ['none', 'uppercase', 'sentence', 'smallcaps'], true) ? $f['text_case'] : 'none',
                    'max_length' => is_numeric($f['max_length'] ?? null) && $f['max_length'] > 0 ? (int) $f['max_length'] : null,
                    'max_lines' => is_numeric($f['max_lines'] ?? null) && $f['max_lines'] > 0 ? (int) $f['max_lines'] : null,
                    'required' => (bool) ($f['required'] ?? false),
                    'date_mode' => ($f['date_mode'] ?? 'current') === 'user' ? 'user' : 'current',
                    'placeholder' => ($p = (string) ($f['placeholder'] ?? '')) !== '' ? mb_substr($p, 0, 2000) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $id = (string) ($f['id'] ?? '');
                if (ctype_digit($id)) {
                    DB::table('template_fields')->insert(['field_id' => (int) $id] + $row);
                } else {
                    $map[$id] = DB::table('template_fields')->insertGetId($row);
                }
            }
        });

        $this->template->touch();

        return $map;
    }

    /** "About this form": shown under its name where students pick a form. */
    #[Renderless]
    public function saveDescription(?string $description): void
    {
        $this->template->update(['description' => ($d = trim((string) $description)) !== '' ? mb_substr($d, 0, 300) : null]);
    }

    /** Public (students can use it) or private. Returns the new status. */
    #[Renderless]
    public function toggleStatus(): string
    {
        $status = $this->template->status === 'active' ? 'inactive' : 'active';
        $this->template->update(['status' => $status]);

        return $status;
    }

    /** The page's paper size, e.g. to match a PDF page that was converted into the background. */
    #[Renderless]
    public function setPaper(string $size, string $orientation, $widthCm = null, $heightCm = null): void
    {
        $custom = $size === 'Custom' && is_numeric($widthCm) && is_numeric($heightCm) && $widthCm > 0 && $heightCm > 0;
        if (! $custom && ! array_key_exists($size, self::SIZES)) {
            return;
        }

        $this->version->update([
            'document_size' => $custom ? 'Custom' : $size,
            'orientation' => $orientation === 'landscape' ? 'landscape' : 'portrait',
            'custom_width' => $custom ? $widthCm : null,
            'custom_height' => $custom ? $heightCm : null,
        ]);

        $this->dispatch('paperUpdated', page: $this->pageSize(), paper: $this->paperInfo());
    }

    /** A new background image (or a converted PDF page). The old file is kept for records made from it. */
    public function updatedImage(): void
    {
        $this->validate(['image' => 'image|max:2048']);

        $path = $this->image->store('templates', 'public');
        $this->version->update(['image_path' => $path]);
        $this->image = null;

        $this->dispatch('imageUploaded', url: Storage::disk('public')->url($path));
    }

    // ── instructions ────────────────────────────────────────────────────────

    private function loadInstructions(): void
    {
        $this->instructions = DB::table('template_instructions')
            ->where('version_id', $this->version->version_id)
            ->orderBy('step_number')
            ->get()
            ->map(fn ($s) => [
                'instruction_id' => $s->instruction_id,
                'step_number' => $s->step_number,
                'description' => $s->description,
            ])->values()->all();
    }

    #[Renderless]
    public function addInstruction($description)
    {
        $description = trim((string) $description);
        if ($description === '') {
            return null;
        }

        $step = count($this->instructions) + 1;
        $id = DB::table('template_instructions')->insertGetId([
            'version_id' => $this->version->version_id,
            'step_number' => $step,
            'description' => mb_substr($description, 0, 1000),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $new = ['instruction_id' => $id, 'step_number' => $step, 'description' => $description];
        $this->instructions[] = $new;

        return $new;
    }

    #[Renderless]
    public function updateInstruction($instructionId, $description): void
    {
        $description = trim((string) $description);
        if ($description === '') {
            return;
        }
        DB::table('template_instructions')
            ->where('version_id', $this->version->version_id)
            ->where('instruction_id', $instructionId)
            ->update(['description' => mb_substr($description, 0, 1000), 'updated_at' => now()]);
    }

    #[Renderless]
    public function deleteInstruction($instructionId): void
    {
        DB::table('template_instructions')
            ->where('version_id', $this->version->version_id)
            ->where('instruction_id', $instructionId)
            ->delete();

        $this->instructions = array_values(array_filter($this->instructions, fn ($i) => $i['instruction_id'] != $instructionId));
    }

    #[Renderless]
    public function reorderInstructions($orderedIds): void
    {
        foreach (array_values((array) $orderedIds) as $index => $id) {
            DB::table('template_instructions')
                ->where('version_id', $this->version->version_id)
                ->where('instruction_id', $id)
                ->update(['step_number' => $index + 1]);
        }
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function pageSize(): array
    {
        $v = $this->version->fresh();
        if ($v->document_size === 'Custom' && $v->custom_width && $v->custom_height) {
            // custom sizes are in cm; 1 cm = 37.8 px at 96 DPI
            [$w, $h] = [round($v->custom_width * 37.8), round($v->custom_height * 37.8)];
        } else {
            [$w, $h] = self::SIZES[$v->document_size] ?? self::SIZES['A4'];
        }

        return $v->orientation === 'landscape' ? ['width' => $h, 'height' => $w] : ['width' => $w, 'height' => $h];
    }

    private function paperInfo(): array
    {
        $v = $this->version->fresh();

        return [
            'size' => $v->document_size ?: 'A4',
            'orientation' => $v->orientation ?: 'portrait',
            'width' => $v->custom_width,
            'height' => $v->custom_height,
        ];
    }

    private function mapDataType($type)
    {
        return match ($type) {
            'number' => 'number',
            'date' => 'date',
            default => 'text',
        };
    }

    private function mapBackType($dataType, $fieldType)
    {
        if ($fieldType === 'paragraph') {
            return 'paragraph';
        }

        return match ($dataType) {
            'number' => 'number',
            'date' => 'date',
            default => 'text',
        };
    }

    public function render()
    {
        return view('livewire.admin.templates.template-editor', [
            'editorConfig' => [
                'fields' => $this->loadFields(),
                'page' => $this->pageSize(),
                'paper' => $this->paperInfo(),
                'imageUrl' => $this->version->image_path ? Storage::disk('public')->url($this->version->image_path) : null,
                'name' => $this->template->name,
                'description' => (string) $this->template->description,
                'status' => $this->template->status ?: 'inactive',
                'systemVariables' => self::SYSTEM_VARIABLES,
                'instructions' => $this->instructions,
                'systemPreview' => [
                    'current_semester' => currentSemester() ?? 'Current semester',
                    'current_academic_year' => currentAcademicYear() ?? 'Current school year',
                ],
            ],
        ])->layout('layouts.editor', ['title' => $this->template->name]);
    }
}
