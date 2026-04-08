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

class NewTransaction extends Component
{
    public $templates = [];
    public $selectedTemplateId = null;
    public $selectedTemplate = null;
    public $selectedWorkspaceId = null;
    public $fields = [];
    public $formValues = [];
    public $instructions = [];
    public $savedMessage = null;
    public $pdfUrl = null;
    public $lastSavedAt = null;
    public $search = '';

    public function mount($template = null): void
    {
        $this->loadTemplates();

        if ($template) {
            $this->selectTemplate((int) $template);
        }
    }

    public function updatedSearch(): void
    {
        $this->loadTemplates();
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
        foreach ($values as $key => $value) {
            $this->formValues[$key] = $value;
        }

        $this->validate($this->rules(), $this->messages());

        $workspace = $this->currentWorkspace();
        $workspace->update([
            'field_values' => $this->normalizedFieldValues(),
        ]);

        $savedAt = optional($workspace->fresh()->updated_at)->diffForHumans();

        $this->dispatch('workspace-saved', [
            'message' => 'Workspace saved.',
            'savedAt' => $savedAt,
        ]);
    }

    #[Renderless]
    public function savePdf(array $values = []): void
    {
        \Log::info('savePdf called', ['values' => $values]);

        foreach ($values as $key => $value) {
            $this->formValues[$key] = $value;
        }

        // ↓ Replace your old $this->validate() line with this block
        try {
            $this->validate($this->rules(), $this->messages());
            \Log::info('validation passed');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('validation failed', ['error' => $e->getMessage()]);
            $this->dispatch('pdf-error', message: $e->getMessage());
            return;
        }

        try {
            \Log::info('starting pdf generation');

            $pdfService = app(StudentDocumentPdfService::class);
            $workspace = $this->currentWorkspace();
            $template = Template::with('currentVersion')->findOrFail($this->selectedTemplateId);
                \Log::info('image path', [
                    'image_path'  => $template->currentVersion->image_path,
                    'full_path'   => Storage::disk('public')->path($template->currentVersion->image_path),
                    'exists'      => file_exists(Storage::disk('public')->path($template->currentVersion->image_path)),
                ]);
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
            \Log::info('dispatching pdf-ready', ['url' => $url]);

            $this->dispatch('pdf-ready', url: $url, savedAt: optional($workspace->fresh()->updated_at)->diffForHumans());

        } catch (\Throwable $e) {
            \Log::error('pdf generation failed', ['error' => $e->getMessage()]);
            $this->dispatch('pdf-error', message: $e->getMessage());
        }
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
            ->with('template')
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
    public function inputFields()
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

    public function render()
    {
        $title = $this->selectedTemplate
            ? 'New Transaction > ' . $this->selectedTemplate['name']
            : 'New Transaction';

        return view('livewire.student.new-transaction')
            ->layout('layouts.app', ['title' => $title]);
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
        $search = trim($this->search);

        $templates = Template::query()
            ->where('status', 'active')
            ->whereNotNull('current_version_id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery->where('name', 'like', '%' . $search . '%');
                });
            })
            ->with([
                'currentVersion.fields' => fn ($query) => $query->orderBy('y_position')->orderBy('x_position'),
                'currentVersion.instructions' => fn ($query) => $query->orderBy('step_number'),
            ])
            ->withCount('studentWorkspaces')
            ->orderBy('name')
            ->get();

        $this->templates = $templates->map(function (Template $template) {
            $description = $template->currentVersion?->instructions?->pluck('description')->filter()->implode(' ');
            $description = trim(Str::limit($description ?: ('Prefilled form for ' . $template->name . '.'), 120));

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
            ];
        })->values()->all();
    }

    private function selectTemplate(int $templateId): void
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

        $workspace = StudentDocumentWorkspace::firstOrCreate(
            [
                'user_id' => Auth::id(),
                'template_id' => $template->template_id,
                'version_id' => $template->currentVersion->version_id,
            ],
            [
                'field_values' => [],
            ]
        );

        $this->selectedTemplateId = $template->template_id;
        $this->selectedTemplate = [
            'template_id' => $template->template_id,
            'name' => $template->name,
            'image_url' => Storage::disk('public')->url($template->currentVersion->image_path),
            'document_size' => $template->currentVersion->document_size,
            'orientation' => $template->currentVersion->orientation,
            'canvas' => $this->canvasDimensions($template->currentVersion),
        ];
        $this->selectedWorkspaceId = $workspace->workspace_id;
        $this->instructions = $template->currentVersion->instructions->map(fn ($instruction) => [
            'step_number' => $instruction->step_number,
            'description' => $instruction->description,
        ])->values()->all();

        $this->fields = $template->currentVersion->fields->map(function ($field) {
            return [
                'id' => (string) $field->field_id,
                'name' => $field->name ?: ('field_' . $field->field_id),
                'label' => $field->label,
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
                'font_size' => (float) $field->font_size,
                'text_color' => $field->text_color,
                'alignment' => $field->alignment,
                'line_height' => (float) ($field->line_height ?? 1.3),
                'letter_spacing' => (float) ($field->letter_spacing ?? 0),
            ];
        })->values()->all();

        $this->formValues = [];
        foreach ($this->fields as $field) {
            $savedValue = data_get($workspace->field_values, $field['name']);
            $this->formValues[$field['name']] = $savedValue ?? $this->defaultValueForField($field);
        }

        $this->pdfUrl = $workspace->generated_pdf_path
            ? Storage::disk('public')->url($workspace->generated_pdf_path)
            : null;
        $this->lastSavedAt = optional($workspace->updated_at)->diffForHumans();
        $this->savedMessage = null;
        $this->resetValidation();
    }

    private function currentWorkspace(): StudentDocumentWorkspace
    {
        return StudentDocumentWorkspace::findOrFail($this->selectedWorkspaceId);
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
            return $this->resolveSystemValue((string) $field['system_key']);
        }

        if (($field['type'] ?? 'text') === 'date' && ($field['date_mode'] ?? 'current') === 'current') {
            return now()->format('m/d/Y');
        }

        $value = $fieldValues[$field['name']] ?? null;
        if ($value === null || $value === '') {
            return (string) ($field['placeholder'] ?? '');
        }

        if (($field['type'] ?? 'text') === 'date') {
            try {
                return Carbon::parse($value)->format('m/d/Y');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        }

        return (string) $value;
    }

    private function resolveSystemValue(string $key): string
    {
        $user = Auth::user();
        $middleInitial = $user->middle_name ? Str::upper(Str::substr($user->middle_name, 0, 1)) . '.' : null;
        $formattedFullName = trim(implode(' ', array_filter([
            $user->first_name,
            $middleInitial,
            $user->last_name,
            $user->suffix,
        ])));
        $programPrefix = $this->programDegreePrefix((string) ($user->program ?? ''));
        $ordinalYear = $this->ordinalYearLevel((string) ($user->year_level ?? ''));
        $programName = $this->normalizedProgramName((string) ($user->program ?? ''));
        $programYearSection = trim(implode(' ', array_filter([
            trim($programPrefix . ' ' . $programName),
            $ordinalYear ? $ordinalYear . '-' . (string) ($user->section ?? '') : (string) ($user->section ?? ''),
        ])));

        return match ($key) {
            'student_number' => (string) ($user->student_number ?? ''),
            'full_name' => $formattedFullName,
            'formatted_full_name' => $formattedFullName,
            'program_year_section' => $programYearSection,
            'first_name' => (string) ($user->first_name ?? ''),
            'middle_name' => (string) ($user->middle_name ?? ''),
            'last_name' => (string) ($user->last_name ?? ''),
            'suffix' => (string) ($user->suffix ?? ''),
            'sex' => (string) ($user->sex ?? ''),
            'date_of_birth' => $user->date_of_birth ? Carbon::parse($user->date_of_birth)->format('m/d/Y') : '',
            'email' => (string) ($user->email ?? ''),
            'contact_number' => (string) ($user->contact_number ?? ''),
            'college' => (string) ($user->college ?? ''),
            'program' => (string) ($user->program ?? ''),
            'section' => (string) ($user->section ?? ''),
            'organization' => (string) ($user->organization ?? ''),
            'year_level' => (string) ($user->year_level ?? ''),
            'academic_status' => (string) ($user->academic_status ?? ''),
            default => '',
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
            $width = round($version->custom_width * 37.8);
            $height = round($version->custom_height * 37.8);
        } else {
            [$width, $height] = $sizes[$version->document_size] ?? $sizes['A4'];
        }

        return $orientation === 'landscape'
            ? ['width' => $height, 'height' => $width]
            : ['width' => $width, 'height' => $height];
    }
}
