<?php

namespace App\Livewire\Admin\Templates;

use App\Livewire\Student\NewTransaction;
use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;

/**
 * Admin › Templates › a template › Student preview: the paper form exactly as students see it
 * (the same view as Student › New transaction), with sample profile details. Nothing is saved.
 * Works for private templates too, so a form can be checked before it is published.
 */
class TemplatePreview extends Component
{
    public int $templateId;

    /** Stand-ins for the student's profile, so every automatic field shows something. */
    private const SAMPLE = [
        'student_number' => '2100123',
        'full_name' => 'Juan Santos Dela Cruz',
        'formatted_full_name' => 'J. Dela Cruz',
        'program_year_section' => 'BSIT 3-A',
        'first_name' => 'Juan',
        'middle_name' => 'Santos',
        'last_name' => 'Dela Cruz',
        'suffix' => '',
        'sex' => 'Male',
        'date_of_birth' => '2004-06-15',
        'email' => 'juan.delacruz@lnu.edu.ph',
        'contact_number' => '09171234567',
        'college' => 'College of Arts and Sciences',
        'program' => 'Bachelor of Science in Information Technology',
        'section' => 'A',
        'organization' => 'Information Technology Society',
        'year_level' => '3',
        'academic_status' => 'Regular',
    ];

    public function mount(int $template): void
    {
        $this->templateId = $template;
    }

    private function titleCase(?string $text): string
    {
        if (! $text) {
            return '';
        }
        $words = explode(' ', Str::title(Str::lower(str_replace(['_', '-'], ' ', $text))));

        return collect($words)->map(fn ($w, $i) => $i > 0 && in_array(Str::lower($w), ['to', 'of', 'the'], true) ? Str::lower($w) : $w)->implode(' ');
    }

    public function render()
    {
        $template = Template::with([
            'currentVersion.fields' => fn ($q) => $q->orderBy('y_position')->orderBy('x_position'),
            'currentVersion.instructions' => fn ($q) => $q->orderBy('step_number'),
        ])->findOrFail($this->templateId);
        $version = $template->currentVersion;
        abort_unless($version, 404);

        $fields = NewTransaction::fieldsFor($version->fields);

        $systemValues = collect($fields)
            ->filter(fn ($f) => ($f['source_type'] ?? 'input') === 'system')
            ->mapWithKeys(fn ($f) => [$f['name'] => match ($f['system_key']) {
                'current_semester' => currentSemester() ?? 'First Semester',
                'current_academic_year' => currentAcademicYear() ?? now()->format('Y') . '-' . now()->addYear()->format('Y'),
                default => self::SAMPLE[$f['system_key']] ?? '',
            }])
            ->all();

        $formValues = collect($fields)->mapWithKeys(fn ($f) => [
            $f['name'] => ($f['type'] === 'date' && ($f['date_mode'] ?? 'current') === 'current') ? now()->toDateString() : null,
        ])->all();

        return view('livewire.admin.templates.template-preview', [
            'template' => [
                'template_id' => $template->template_id,
                'name' => $template->name,
                'description' => $template->description,
                'status' => $template->status,
                'image_url' => $version->image_path ? Storage::disk('public')->url($version->image_path) : '',
                'document_size' => $version->document_size,
                'orientation' => $version->orientation,
                'canvas' => NewTransaction::canvasFor($version),
            ],
            'fields' => $fields,
            'formValues' => $formValues,
            'instructions' => $version->instructions->map(fn ($i) => ['step_number' => $i->step_number, 'description' => $i->description])->values()->all(),
            'systemValues' => $systemValues,
            'titleCase' => fn ($t) => $this->titleCase($t),
        ])->layout('layouts.app', ['title' => 'Preview · ' . $template->name]);
    }
}
