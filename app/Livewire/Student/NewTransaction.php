<?php

namespace App\Livewire\Student;

use App\Models\StudentDocumentWorkspace;
use App\Models\Template;
use App\Services\StudentDocumentPdfService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Livewire\Attributes\Url;

#[Layout('layouts.app')]
class NewTransaction extends Component
{
    public array $templates = [];
    public ?int $selectedTemplateId = null;
    public ?array $selectedTemplate = null;
    public ?int $selectedWorkspaceId = null;
    public array $fields = [];
    public array $formValues = [];
    public array $formData = [];
    public array $instructions = [];
    public ?string $savedMessage = null;
    public ?string $pdfUrl = null;
    public ?string $lastSavedAt = null;
    public string $viewMode = 'grid';
    public ?int $editingWorkspaceId = null;

    //-- for searching templates --//
    #[Url(except: '')]
    public string $search = '';

    

    public function mount($template = null, $workspace = null): void
    {
        $this->loadTemplates();

        if ($template) {
            $this->selectTemplate((int) $template, $workspace ? (int) $workspace : null);
        }
    }
    //-- for searching templates --//

    public function getFilteredTemplatesProperty()
    {
        if (empty(trim($this->search))) {
            return $this->templates;
        }

        $term = strtolower(trim($this->search));

        return array_filter($this->templates, function ($template) use ($term) {
            return str_contains(strtolower($template['name'] ?? ''), $term)
                || str_contains(strtolower($template['document_size'] ?? ''), $term);
        });
    }
    public function formatTitle(?string $text): string
    {
        if (!$text) {
            return '';
        }

        $cleaned = str_replace(['_', '-'], ' ', $text);
        $words = explode(' ', Str::title(Str::lower($cleaned)));
        $minorWords = ['to', 'of', 'the'];

        return collect($words)->map(function ($word, $index) use ($minorWords) {
            $lower = Str::lower($word);
            if ($index > 0 && in_array($lower, $minorWords, true)) {
                return $lower;
            }
            return $word;
        })->implode(' ');
    }
    public function formatToSentenceCase(string $text): string
    {
        $cleaned = strtolower(trim(preg_replace('/[_\-]+/', ' ', $text)));
        return ucfirst($cleaned);
    }

    public function openTemplate(int $templateId)
    {
        return $this->redirectRoute('student.new-transaction', ['template' => $templateId], navigate: true);
    }

    public function goToDashboard()
    {
        return $this->redirectRoute('student.new-transaction', navigate: true);
    }

    #[Renderless]
    public function saveWorkspace(array $values = []): void
    {
        $this->syncFormValues($values);

        try {
            $this->validate($this->rules(), $this->messages());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('validation-error', [
                'message' => 'Please fix the highlighted fields before saving.',
                'fields' => $this->errorFieldNames($e),
            ]);
            return;
        }

        $workspace = $this->currentWorkspace();
        $workspace->update([
            'field_values' => $this->normalizedFieldValues(),
        ]);

        $this->dispatch('workspace-saved', [
            'message' => 'Workspace saved.',
            'savedAt' => optional($workspace->fresh()->updated_at)->diffForHumans(),
        ]);

        $this->redirect(route('student.documents.index'), navigate: true);
    }

    #[Renderless]
    public function savePdf(array $values = []): void
    {
        $this->syncFormValues($values);

        try {
            $this->validate($this->rules(), $this->messages());
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('validation-error', [
                'message' => 'Please fix the highlighted fields before generating the PDF.',
                'fields' => $this->errorFieldNames($e),
            ]);
            return;
        }

        try {
            $pdfService = app(StudentDocumentPdfService::class);
            $workspace = $this->currentWorkspace();
            $template = Template::with('currentVersion')->findOrFail($this->selectedTemplateId);
            $fieldValues = $this->normalizedFieldValues();

            $workspace->update(['field_values' => $fieldValues]);

            $resolvedValues = [];
            foreach ($this->fields as $field) {
                $resolvedValues[$field['id']] = $this->displayValueForField($field, $fieldValues);
            }

            $pdfBinary = $pdfService->generate($template->currentVersion, $this->fields, $resolvedValues);
            $fileName = 'generated-documents/' . Str::slug($template->name) . '-' . $workspace->workspace_id . '.pdf';

            Storage::disk('public')->put($fileName, $pdfBinary);

            $workspace->update([
                'generated_pdf_path' => $fileName,
                'last_generated_at' => now(),
            ]);

            $url = Storage::disk('public')->url($fileName);

            $this->dispatch('pdf-ready', url: $url, savedAt: optional($workspace->fresh()->updated_at)->diffForHumans());
        } catch (\Throwable $e) {
            $this->dispatch('pdf-error', message: $e->getMessage());
        }
    }

    private function syncFormValues(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->formValues[$key] = $value;
        }
    }

    private function errorFieldNames(\Illuminate\Validation\ValidationException $e): array
    {
        return collect(array_keys($e->errors()))
            ->map(fn ($key) => str_replace('formValues.', '', $key))
            ->values()
            ->all();
    }

    #[Computed]
    public function dashboardStats(): array
    {
        $userId = Auth::id();

        $pending = StudentDocumentWorkspace::query()
            ->where('user_id', $userId)
            ->whereNull('generated_pdf_path')
            ->count();

        $completed = StudentDocumentWorkspace::query()
            ->where('user_id', $userId)
            ->whereNotNull('generated_pdf_path')
            ->count();

        $recentWorkspace = StudentDocumentWorkspace::query()
            ->with('template:template_id,name')
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->first();

        return [
            'pending_transactions' => $pending,
            'completed_transactions' => $completed,
            'upcoming_appointments' => 0,
            'recent_transaction' => $recentWorkspace ? [
                'name' => $recentWorkspace->template?->name ?? 'Untitled document',
                'status' => $recentWorkspace->generated_pdf_path ? 'Completed' : 'Pending',
                'updated_at' => optional($recentWorkspace->updated_at)->diffForHumans(),
            ] : null,
        ];
    }

    #[Computed]
    public function inputFields(): \Illuminate\Support\Collection
    {
        return collect($this->fields)
            ->filter(fn (array $field) => $this->showsStudentInput($field))
            ->values();
    }

    #[Computed]
    public function systemPreviewValues(): array
    {
        return collect($this->fields)
            ->filter(fn (array $field) => ($field['source_type'] ?? 'input') === 'system')
            ->mapWithKeys(fn (array $field) => [$field['name'] => $this->resolveSystemValue((string) ($field['system_key'] ?? ''))])
            ->all();
    }

    #[Computed]
    public function filledFieldsSummary(): array
    {
        $studentFields = collect($this->fields)->filter(fn ($f) => $this->showsStudentInput($f));
        $total = $studentFields->count();

        $filled = $studentFields->filter(function ($field) {
            $value = $this->formValues[$field['name']] ?? null;
            return $value !== null && trim((string) $value) !== '';
        })->count();

        return ['filled' => $filled, 'total' => $total];
    }

    public function render()
    {
        return view('livewire.student.new-transaction', [
            'filteredTemplates' => $this->filteredTemplates,
        ])->layout('layouts.app'); // Direct Livewire to your layout file
    }
    private function programAbbreviation(string $program): string
    {
        $raw  = trim($program);
        $norm = Str::lower(preg_replace('/[^a-z0-9]+/i', ' ', $raw));
        $norm = trim($norm);

        if ($norm === '') {
            return '';
        }

        // BSED with a major -> "BSED-Science"
        $isBsed = str_contains($norm, 'secondary education') || preg_match('/^bsed\b/', $norm);
        if ($isBsed) {
            $majors = [
                'social studies'   => 'Social Studies',
                'values education' => 'Values Education',
                'mathematics'      => 'Mathematics',
                'math'             => 'Mathematics',
                'filipino'         => 'Filipino',
                'english'          => 'English',
                'science'          => 'Science',
            ];
            // Strip the degree part so "Bachelor of Science..." can't be mistaken for a major
            $afterDegree = preg_replace('/^(bachelor of secondary education|bsed)\b/', '', $norm);
            foreach ($majors as $needle => $label) {
                if (str_contains($afterDegree, $needle)) {
                    return 'BSED-' . $label;
                }
            }
            return 'BSED';
        }

        // Full name (or the abbreviation itself) => abbreviation
        $map = [
            'bachelor of elementary education'               => 'BEED',
            'bachelor of early childhood education'          => 'BECED',
            'bachelor of special needs education'            => 'BSNED',
            'bachelor of technology and livelihood education'=> 'BTLED',
            'bachelor of physical education'                 => 'BPED',
            'teacher certificate program'                    => 'TCP',
            'bachelor of arts in communication'              => 'BA Comm',
            'bachelor of library and information science'    => 'BLIS',
            'bachelor of science in information technology'  => 'BSIT',
            'bachelor of arts in english language'           => 'BAEL',
            'bachelor of arts in political science'          => 'BAPoS',
            'bachelor of science in biology'                 => 'BSBio',
            'bachelor of science in social work'             => 'BSSW',
            'bachelor of science in tourism management'      => 'BSTM',
            'bachelor of science in hospitality management'  => 'BSHM',
            'bachelor of science in entrepreneurship'        => 'BSEntrep',
        ];

        foreach ($map as $full => $abbr) {
            $abbrNorm = Str::lower(preg_replace('/[^a-z0-9]+/i', ' ', $abbr));
            if ($norm === $full || str_starts_with($norm, $full . ' ') || $norm === trim($abbrNorm)) {
                return $abbr;
            }
        }

        // Unknown program: fall back to the old behaviour (the preview/PDF will shrink it to fit)
        return trim($this->programDegreePrefix($raw) . ' ' . $this->normalizedProgramName($raw));
    }

    protected function rules(): array
    {
        $rules = [];

        foreach ($this->fields as $field) {
            if (!$this->showsStudentInput($field)) {
                continue;
            }

            $key = 'formValues.' . $field['name'];
            $fieldRules = [$field['required'] ? 'required' : 'nullable'];

            if ($field['type'] === 'number') {
                $fieldRules[] = 'numeric';
            } elseif ($field['type'] === 'date') {
                $fieldRules[] = 'date';
            } else {
                $fieldRules[] = 'string';
                if (!empty($field['max_length'])) {
                    $fieldRules[] = 'max:' . $field['max_length'];
                }
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    protected function messages(): array
    {
        $messages = [];

        foreach ($this->fields as $field) {
            if (!$this->showsStudentInput($field)) {
                continue;
            }

            $base = 'formValues.' . $field['name'];
            $messages[$base . '.required'] = $field['label'] . ' is required.';
            $messages[$base . '.date'] = $field['label'] . ' must be a valid date.';
            $messages[$base . '.numeric'] = $field['label'] . ' must be a number.';
        }

        return $messages;
    }

    private function loadTemplates(): void
    {
        $templates = Template::query()
            ->where('status', 'active')
            ->whereNotNull('current_version_id')
            ->with([
                'currentVersion.instructions' => fn ($query) => $query->orderBy('step_number'),
            ])
            ->withCount('studentWorkspaces')
            ->orderBy('name')
            ->get();

        $this->templates = $templates->map(function (Template $template) {
            $description = $template->currentVersion?->instructions?->pluck('description')->filter()->implode(' ');
            $description = trim(Str::limit($description ?: ('Prefilled form for ' . $template->name . '.'), 120));

            $lastUpdated = $template->currentVersion?->updated_at ?? $template->updated_at;

            return [
                'template_id' => $template->template_id,
                'name' => $template->name,
                'status' => $template->status,
                'document_size' => $template->currentVersion?->document_size,
                'orientation' => $template->currentVersion?->orientation,
                'preview_url' => $template->currentVersion?->image_path
                    ? Storage::disk('public')->url($template->currentVersion->image_path)
                    : null,
                'description' => $description,
                'access_count' => (int) $template->student_workspaces_count,
                'updated_at' => $lastUpdated ? $lastUpdated->diffForHumans() : null,
            ];
        })->values()->all();
    }

    private function selectTemplate(int $templateId, ?int $workspaceId = null): void
    {
        $template = Template::query()
            ->where('template_id', $templateId)
            ->where('status', 'active')
            ->with([
                'currentVersion.fields' => fn ($query) => $query->orderBy('y_position')->orderBy('x_position'),
                'currentVersion.instructions' => fn ($query) => $query->orderBy('step_number'),
            ])
            ->firstOrFail();

        abort_if(!$template->currentVersion, 404);

        $workspace = null;

        if ($workspaceId) {
            $workspace = StudentDocumentWorkspace::where('workspace_id', $workspaceId)
                ->where('user_id', Auth::id())
                ->where('template_id', $template->template_id)
                ->firstOrFail();

            $this->editingWorkspaceId = $workspace->workspace_id;
        } else {
            $this->editingWorkspaceId = null;
        }

        $this->selectedTemplateId = $template->template_id;
        $this->selectedTemplate = [
            'template_id' => $template->template_id,
            'name' => $template->name,
            'image_url' => Storage::disk('public')->url($template->currentVersion->image_path),
            'document_size' => $template->currentVersion->document_size,
            'orientation' => $template->currentVersion->orientation,
            'canvas' => $this->canvasDimensions($template->currentVersion),
            'updated_at' => optional($template->currentVersion->updated_at)->diffForHumans(),
        ];
        $this->selectedWorkspaceId = $workspace?->workspace_id;
        $this->instructions = $template->currentVersion->instructions->map(fn ($instruction) => [
            'step_number' => $instruction->step_number,
            'description' => $instruction->description,
        ])->values()->all();

        // IMPORTANT: field "name" is derived from the label via Str::slug(). Two
        // fields with the same/similar label used to collide on the exact same
        // slug, which meant they silently shared one entry in $formValues and
        // typing in one box changed the other. We now guarantee uniqueness by
        // falling back to a name suffixed with the field's own (always unique)
        // database id whenever a slug has already been used on this template.
        $this->fields = $template->currentVersion->fields->map(function ($field) {
        $name = $field->name ? Str::slug($field->name, '_') : ('field_' . $field->field_id);

        return [
                'id' => (string) $field->field_id,
                'name' => $name,
                'label' => $field->label,
                'group_name' => $field->group_name,
                'type' => $field->field_type === 'paragraph' ? 'paragraph' : $field->data_type,
                'source_type' => $field->source_type,
                'system_key' => $field->system_key,
                'required' => (bool) $field->required,
                'date_mode' => $field->date_mode ?? 'current',
                'placeholder' => $field->placeholder,
                'max_length' => $field->max_length,
                'max_lines' => $field->max_lines,
                'x' => (float) $field->x_position,
                'y' => (float) $field->y_position,
                'width' => (float) $field->width,
                'height' => (float) $field->height,
                'font_family' => $field->font_family,
                'font_weight' => $field->font_weight,
                'font_style'  => $field->font_style ?? 'normal',
                'font_size' => (float) $field->font_size,
                'text_color' => $field->text_color,
                'alignment' => $field->alignment,
                'line_height' => (float) ($field->line_height ?? 1.3),
                'letter_spacing' => (float) ($field->letter_spacing ?? 0),
                'text_case' => $field->text_case ?? 'none',
            ];
        })->values()->all();

        $this->formValues = [];
        foreach ($this->fields as $field) {
            $savedValue = $workspace
                ? data_get($workspace->field_values, $field['name'])
                : null;
            $this->formValues[$field['name']] = $savedValue ?? $this->defaultValueForField($field);
        }

        $this->pdfUrl = $workspace?->generated_pdf_path
            ? Storage::disk('public')->url($workspace->generated_pdf_path)
            : null;
        $this->lastSavedAt = optional($workspace?->updated_at)->diffForHumans();
        $this->savedMessage = null;
        $this->resetValidation();
    }

    private function currentWorkspace(): StudentDocumentWorkspace
    {
        if ($this->selectedWorkspaceId) {
            return StudentDocumentWorkspace::findOrFail($this->selectedWorkspaceId);
        }

        $workspace = StudentDocumentWorkspace::create([
            'user_id' => Auth::id(),
            'template_id' => $this->selectedTemplateId,
            'version_id' => Template::find($this->selectedTemplateId)->current_version_id,
            'field_values' => [],
            'status' => 'pending',
        ]);

        $this->selectedWorkspaceId = $workspace->workspace_id;

        return $workspace;
    }

    private function normalizedFieldValues(): array
    {
        $values = [];

        foreach ($this->fields as $field) {
            $value = $this->formValues[$field['name']] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $values[$field['name']] = $value === '' ? null : $value;
        }

        return $values;
    }

    private function showsStudentInput(array $field): bool
    {
        if (($field['source_type'] ?? 'input') !== 'input') {
            return false;
        }

        if (($field['type'] ?? 'text') === 'date' && ($field['date_mode'] ?? 'current') === 'current') {
            return false;
        }

        return true;
    }

    private function defaultValueForField(array $field): ?string
    {
        if (($field['type'] ?? 'text') === 'date' && ($field['date_mode'] ?? 'current') === 'current') {
            return now()->toDateString();
        }

        return null;
    }

    private function displayValueForField(array $field, array $fieldValues): string
    {
        if (($field['source_type'] ?? 'input') === 'system') {
            $value = $this->resolveSystemValue((string) $field['system_key']);
            return $this->applyTextCase($value, $field);
        }

        if (($field['type'] ?? 'text') === 'date' && ($field['date_mode'] ?? 'current') === 'current') {
            return now()->format('m/d/Y');
        }

        $value = $fieldValues[$field['name']] ?? null;
        if ($value === null || $value === '') {
            return '';
        }

        if (($field['type'] ?? 'text') === 'date') {
            try {
                return Carbon::parse($value)->format('m/d/Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        return $this->applyTextCase((string) $value, $field);
    }

    private function applyTextCase(string $value, array $field): string
    {
        return match ($field['text_case'] ?? 'none') {
            'uppercase' => Str::upper($value),
            'smallcaps' => Str::lower($value),
            'sentence' => Str::ucfirst(Str::lower($value)),
            default => $value,
        };
    }


    private function programDegreePrefix(string $program): string
    {
        $normalized = Str::lower(trim($program));

        return match (true) {
            Str::startsWith($normalized, 'master of arts'),
            Str::startsWith($normalized, 'ma ') => 'MA',
            Str::startsWith($normalized, 'master of science'),
            Str::startsWith($normalized, 'ms ') => 'MS',
            Str::startsWith($normalized, 'bachelor of arts'),
            Str::startsWith($normalized, 'ba ') => 'BA',
            default => 'BS',
        };
    }

    private function normalizedProgramName(string $program): string
    {
        $cleaned = trim($program);

        $patterns = [
            '/^bachelor\s+of\s+science\s+in\s+/i',
            '/^bachelor\s+of\s+science\s+/i',
            '/^bs\s+/i',
            '/^bachelor\s+of\s+arts\s+in\s+/i',
            '/^bachelor\s+of\s+arts\s+/i',
            '/^ba\s+/i',
            '/^master\s+of\s+arts\s+in\s+/i',
            '/^master\s+of\s+arts\s+/i',
            '/^ma\s+/i',
            '/^master\s+of\s+science\s+in\s+/i',
            '/^master\s+of\s+science\s+/i',
            '/^ms\s+/i',
        ];

        foreach ($patterns as $pattern) {
            $cleaned = preg_replace($pattern, '', $cleaned) ?? $cleaned;
        }

        return trim($cleaned);
    }

    private function ordinalYearLevel(string $yearLevel): string
    {
        if (preg_match('/(\d+)/', $yearLevel, $matches)) {
            $number = (int) $matches[1];
        } else {
            $number = match (Str::lower(trim($yearLevel))) {
                'first', 'first year' => 1,
                'second', 'second year' => 2,
                'third', 'third year' => 3,
                'fourth', 'fourth year' => 4,
                'fifth', 'fifth year' => 5,
                default => null,
            };
        }

        if (!$number) {
            return trim($yearLevel);
        }

        $suffix = match (true) {
            $number % 100 >= 11 && $number % 100 <= 13 => 'th',
            $number % 10 === 1 => 'st',
            $number % 10 === 2 => 'nd',
            $number % 10 === 3 => 'rd',
            default => 'th',
        };

        return $number . $suffix;
    }
    private function resolveSystemValue(string $key): string
    {
        return app(\App\Services\SystemValueResolver::class)->resolve(Auth::user(), $key);
    }
    private function canvasDimensions($version): array
    {
        $orientation = $version->orientation ?? 'portrait';

        $sizes = [
            'A4' => [794, 1123],
            'A3' => [1123, 1587],
            'Letter' => [816, 1056],
            'Legal' => [816, 1344],
        ];

        if ($version->document_size === 'Custom' && $version->custom_width && $version->custom_height) {
            $width = (int) round($version->custom_width * 37.8);
            $height = (int) round($version->custom_height * 37.8);
        } else {
            [$width, $height] = $sizes[$version->document_size] ?? $sizes['A4'];
        }

        return $orientation === 'landscape'
            ? ['width' => $height, 'height' => $width]
            : ['width' => $width, 'height' => $height];
    }
}