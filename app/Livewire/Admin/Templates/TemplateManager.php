<?php

namespace App\Livewire\Admin\Templates;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Template;
use App\Models\TemplateVersion;
use Illuminate\Support\Facades\Auth;

class TemplateManager extends Component
{
    use WithFileUploads;

    public $templates;

    public $showPanel = false;

    public $name;
    public $paper_size = 'A4';
    public $orientation = 'portrait';
    public $image;

    public $custom_width;
    public $custom_height;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'orientation' => 'required|in:portrait,landscape',
            'image' => 'required|image|max:2048',
            'custom_width' => 'nullable|numeric|min:1',
            'custom_height' => 'nullable|numeric|min:1',
        ];
    }

    public function mount()
    {
        $this->loadTemplates();
    }

    public function loadTemplates()
    {
        $this->templates = Template::with('currentVersion')->latest()->get();
    }

    public function openPanel()
    {
        $this->resetForm();
        $this->showPanel = true;
    }

    public function closePanel()
    {
        $this->showPanel = false;
    }

    public function resetForm()
    {
        $this->name = '';
        $this->paper_size = 'A4';
        $this->orientation = 'portrait';
        $this->image = null;
        $this->custom_width = null;
        $this->custom_height = null;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    /*
    |--------------------------------------------------------------------------
    | CUSTOM LOGIC (FIXED)
    |--------------------------------------------------------------------------
    */

    public function updatedCustomWidth()
    {
        $this->handleCustomSelection();
    }

    public function updatedCustomHeight()
    {
        $this->handleCustomSelection();
    }

    private function handleCustomSelection()
    {
        if ($this->custom_width || $this->custom_height) {
            $this->paper_size = null; // remove highlight
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SAVE
    |--------------------------------------------------------------------------
    */

    public function save()
    {
        $this->validate();

        
        if (!$this->paper_size && !($this->custom_width && $this->custom_height)) {
            $this->addError('paper_size', 'Please select a paper size or enter custom dimensions.');
            return;
        }

        $path = $this->image->store('templates', 'public');

        $isCustom = $this->custom_width && $this->custom_height;

        $template = Template::create([
            'name' => $this->name,
            'created_by' => Auth::id(),
        ]);

        $version = TemplateVersion::create([
            'template_id' => $template->template_id,
            'image_path' => $path,
            'document_size' => $isCustom ? 'Custom' : $this->paper_size,
            'orientation' => $this->orientation,
            'custom_width' => $isCustom ? $this->custom_width : null,
            'custom_height' => $isCustom ? $this->custom_height : null,
            'version_number' => 1,
            'is_active' => true,
        ]);

        $template->update([
            'current_version_id' => $version->version_id
        ]);

        return redirect()->route('admin.templates.editor', $template->template_id);
    }

    public function editTemplate($id)
    {
        return redirect()->route('admin.templates.editor', $id);
    }

    public function deleteTemplate($id)
    {
        $template = Template::findOrFail($id);
        // Delete associated image files
        if ($template->currentVersion && $template->currentVersion->image_path) {
            \Storage::disk('public')->delete($template->currentVersion->image_path);
        }
        $template->versions()->delete();
        $template->delete();
        $this->loadTemplates();
    }

    public function render()
    {
        return view('livewire.admin.templates.template-manager')
            ->layout('layouts.app', ['title' => 'Template Manager']);
    }
}