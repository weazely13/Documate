<?php

namespace App\Livewire\Admin\Templates;

use App\Enums\DocumentCategory;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Admin › Templates & Uploads › Templates: the paper forms, laid out like a design tool's home.
 * New forms start from an image or a PDF page (converted in the browser, resources/js/pdf-to-image.js).
 */
class TemplateManager extends Component
{
    use WithFileUploads;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $filterCategory = '';

    #[Url(as: 'sort', except: 'recent')]
    public string $sort = 'recent';

    // The "New template" dialog
    public bool $showCreate = false;
    public string $name = '';
    public string $description = '';
    public string $category = '';
    public ?string $paper_size = 'A4';
    public string $orientation = 'portrait';
    public $custom_width = null;
    public $custom_height = null;
    public $image;

    public ?int $confirmDeleteId = null;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:300',
            'category' => ['required', Rule::enum(DocumentCategory::class)],
            'orientation' => 'required|in:portrait,landscape',
            'paper_size' => 'nullable|in:A4,Letter,Legal,A3,Custom',
            'image' => 'required|image|max:2048',
            'custom_width' => 'nullable|numeric|min:1|max:200',
            'custom_height' => 'nullable|numeric|min:1|max:200',
        ];
    }

    protected $messages = [
        'image.required' => 'Add the form: an image or a page of a PDF.',
        'image.max' => 'The image must be 2 MB or smaller.',
        'category.required' => 'Choose where students will find this form.',
    ];

    /** Open the dialog, optionally with a paper size already picked (the "Start a form" cards). */
    public function openCreate(?string $size = null): void
    {
        $this->resetErrorBag();
        $this->reset(['name', 'description', 'category', 'custom_width', 'custom_height', 'image']);
        $this->paper_size = in_array($size, ['A4', 'Letter', 'Legal', 'A3'], true) ? $size : 'A4';
        $this->orientation = 'portrait';
        $this->showCreate = true;
    }

    public function closeCreate(): void
    {
        $this->showCreate = false;
        $this->reset(['image']);
    }

    /** A converted PDF page tells us its paper; the dialog takes it as the form's size. */
    public function usePaper(string $size, string $orientation, $widthCm = null, $heightCm = null): void
    {
        $this->orientation = $orientation === 'landscape' ? 'landscape' : 'portrait';
        if ($size === 'Custom' && is_numeric($widthCm) && is_numeric($heightCm)) {
            $this->paper_size = 'Custom';
            $this->custom_width = $widthCm;
            $this->custom_height = $heightCm;
        } elseif (in_array($size, ['A4', 'Letter', 'Legal', 'A3'], true)) {
            $this->paper_size = $size;
            $this->custom_width = $this->custom_height = null;
        }
    }

    public function save()
    {
        $this->validate();

        $isCustom = $this->paper_size === 'Custom';
        if ($isCustom && ! ($this->custom_width && $this->custom_height)) {
            $this->addError('custom_width', 'Enter the width and height in cm.');

            return null;
        }

        $path = $this->image->store('templates', 'public');

        $template = DB::transaction(function () use ($path, $isCustom) {
            $template = Template::create([
                'name' => trim($this->name),
                'description' => trim($this->description) ?: null,
                'category' => $this->category,
                'created_by' => Auth::id(),
            ]);

            $version = TemplateVersion::create([
                'template_id' => $template->template_id,
                'image_path' => $path,
                'document_size' => $isCustom ? 'Custom' : ($this->paper_size ?: 'A4'),
                'orientation' => $this->orientation,
                'custom_width' => $isCustom ? $this->custom_width : null,
                'custom_height' => $isCustom ? $this->custom_height : null,
                'version_number' => 1,
                'is_active' => true,
            ]);

            $template->update(['current_version_id' => $version->version_id]);

            return $template;
        });

        return redirect()->route('admin.templates.editor', $template->template_id);
    }

    /** A private copy of a template: its page, fields and instructions. */
    public function duplicate(int $id): void
    {
        $source = Template::with('currentVersion')->findOrFail($id);
        $v = $source->currentVersion;

        DB::transaction(function () use ($source, $v) {
            $copy = Template::create([
                'name' => Str::limit('Copy of ' . $source->name, 250, ''),
                'description' => $source->description,
                'category' => $source->category?->value ?? $source->category,
                'created_by' => Auth::id(),
                'status' => 'inactive',
            ]);

            if (! $v) {
                return;
            }

            $image = null;
            if ($v->image_path && Storage::disk('public')->exists($v->image_path)) {
                $image = 'templates/' . Str::random(32) . '.' . pathinfo($v->image_path, PATHINFO_EXTENSION);
                Storage::disk('public')->copy($v->image_path, $image);
            }

            $version = TemplateVersion::create([
                'template_id' => $copy->template_id,
                'image_path' => $image,
                'document_size' => $v->document_size,
                'orientation' => $v->orientation,
                'custom_width' => $v->custom_width,
                'custom_height' => $v->custom_height,
                'version_number' => 1,
                'is_active' => true,
            ]);
            $copy->update(['current_version_id' => $version->version_id]);

            foreach (['template_fields' => 'field_id', 'template_instructions' => 'instruction_id'] as $table => $key) {
                DB::table($table)->where('version_id', $v->version_id)->orderBy($key)->get()
                    ->each(function ($row) use ($table, $key, $version) {
                        $data = (array) $row;
                        unset($data[$key]);
                        $data['version_id'] = $version->version_id;
                        $data['created_at'] = $data['updated_at'] = now();
                        DB::table($table)->insert($data);
                    });
            }
        });

        session()->flash('message', 'Duplicated "' . $source->name . '". The copy is private until you publish it.');
    }

    public function toggleStatus(int $id): void
    {
        $t = Template::findOrFail($id);
        $t->update(['status' => $t->status === 'active' ? 'inactive' : 'active']);
    }

    public function setCategory(int $id, string $category): void
    {
        Template::whereKey($id)->update(['category' => DocumentCategory::tryFrom($category)?->value]);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function deleteTemplate(): void
    {
        if (! $this->confirmDeleteId) {
            return;
        }
        // Hidden, not erased: transactions made from it keep their form image, fields, preview and PDF.
        $t = Template::find($this->confirmDeleteId);
        $t?->delete();
        $this->confirmDeleteId = null;
        if ($t) {
            session()->flash('message', '"' . $t->name . '" was deleted. Records made from it are kept.');
        }
    }

    public function render()
    {
        $all = Template::with('currentVersion')->withCount('studentWorkspaces')->get();

        $fieldCounts = DB::table('template_fields')
            ->whereIn('version_id', $all->pluck('current_version_id')->filter())
            ->selectRaw('version_id, count(*) as n')
            ->groupBy('version_id')
            ->pluck('n', 'version_id');

        $q = Str::lower(trim($this->search));
        $templates = $all
            ->when($q !== '', fn ($c) => $c->filter(fn ($t) => Str::contains(Str::lower($t->name), $q)))
            ->when($this->filterCategory === 'none', fn ($c) => $c->filter(fn ($t) => ! $t->category))
            ->when($this->filterCategory !== '' && $this->filterCategory !== 'none', fn ($c) => $c->filter(fn ($t) => ($t->category?->value ?? $t->category) === $this->filterCategory))
            ->pipe(fn ($c) => match ($this->sort) {
                'name' => $c->sortBy(fn ($t) => Str::lower($t->name)),
                'used' => $c->sortByDesc('student_workspaces_count'),
                default => $c->sortByDesc('updated_at'),
            })
            ->values();

        $categories = collect(DocumentCategory::cases())
            ->reject(fn ($c) => $c === DocumentCategory::Other)
            ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label(), 'count' => $all->filter(fn ($t) => ($t->category?->value ?? $t->category) === $c->value)->count()])
            ->filter(fn ($c) => $c['count'] > 0)
            ->values();

        return view('livewire.admin.templates.template-manager', [
            'templates' => $templates,
            'fieldCounts' => $fieldCounts,
            'totalCount' => $all->count(),
            'publicCount' => $all->where('status', 'active')->count(),
            'uncategorised' => $all->filter(fn ($t) => ! $t->category)->count(),
            'categories' => $categories,
            'allCategories' => collect(DocumentCategory::cases())->reject(fn ($c) => $c === DocumentCategory::Other)->values(),
            'confirming' => $this->confirmDeleteId ? $all->firstWhere('template_id', $this->confirmDeleteId) : null,
        ])->layout('layouts.app', ['title' => 'Templates']);
    }
}
