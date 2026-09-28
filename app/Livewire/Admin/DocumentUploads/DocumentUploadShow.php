<?php

namespace App\Livewire\Admin\DocumentUploads;

use App\Models\StudentDocumentWorkspace;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Models\DocumentScanEvent;
use Illuminate\Support\Facades\Auth;

class DocumentUploadShow extends Component
{
    use WithFileUploads;

    public StudentDocumentWorkspace $workspace;
    public $supportingFile = null;
    public ?string $referenceImageBase64 = null;
    public string $lastKnownStatus = '';

    public function mount(StudentDocumentWorkspace $workspace): void
    {
        $this->workspace = $workspace->load(['template.currentVersion.fields', 'user', 'appointments']);
        $this->lastKnownStatus = $this->workspace->analysis_status ?? 'none';
    }

    #[Computed]
    public function fields(): array
    {
        $fields = $this->workspace->template?->currentVersion?->fields;
        if (! $fields) return [];

        return $fields->sortBy('x_position')->sortBy('y_position')->map(function ($f) {
            $name = $f->name ? Str::slug($f->name, '_') : ('field_' . $f->field_id);
            return [
                'id' => (string) $f->field_id, 'name' => $name, 'label' => $f->label,
                'type' => $f->field_type === 'paragraph' ? 'paragraph' : $f->data_type,
                'x' => (float) $f->x_position, 'y' => (float) $f->y_position,
                'width' => (float) $f->width, 'height' => (float) $f->height,
                'font_family' => $f->font_family, 'font_weight' => $f->font_weight,
                'font_size' => (float) $f->font_size, 'text_color' => $f->text_color,
                'alignment' => $f->alignment, 'line_height' => (float) ($f->line_height ?? 1.3),
                'letter_spacing' => (float) ($f->letter_spacing ?? 0), 'text_case' => $f->text_case ?? 'none',
            ];
        })->values()->all();
    }

    public function pollStatus(): void
    {
        $this->workspace->refresh();

        $current = $this->workspace->processing
            ? 'processing:' . $this->workspace->processing_stage
            : ($this->workspace->analysis_status ?? 'none');

        if ($current !== $this->lastKnownStatus) {
            $this->lastKnownStatus = $current;
            $this->dispatch('scan-status-changed')->to(\App\Livewire\Admin\ScanStatusBar::class);
        }
    }

    #[Computed]
    public function canvas(): array
    {
        $version = $this->workspace->template->currentVersion;
        $orientation = $version->orientation ?? 'portrait';
        $sizes = ['A4' => [794, 1123], 'A3' => [1123, 1587], 'Letter' => [816, 1056], 'Legal' => [816, 1344]];
        if ($version->document_size === 'Custom' && $version->custom_width && $version->custom_height) {
            $width = round($version->custom_width * 37.8);
            $height = round($version->custom_height * 37.8);
        } else {
            [$width, $height] = $sizes[$version->document_size] ?? $sizes['A4'];
        }
        return $orientation === 'landscape' ? ['width' => $height, 'height' => $width] : ['width' => $width, 'height' => $height];
    }

    #[Computed]
    public function canUpload(): bool
    {
        return $this->workspace->needsDocumentVerification();
    }

    #[Computed]
    public function supportingFileUrl(): ?string
    {
        return $this->workspace->supporting_file_path
            ? Storage::disk('public')->url($this->workspace->supporting_file_path)
            : null;
    }

    #[Computed]
    public function supportingFileIsImage(): bool
    {
        return $this->workspace->isSupportingFileImage();
    }

    public function uploadSupportingFile(): void
    {
        abort_unless($this->canUpload, 403);

        $this->validate(['supportingFile' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240']);

        // Wipe any prior scan history for THIS workspace before starting a new
        // run — a re-upload/rerun replaces the old timeline, it never stacks
        // on top of it.
        DocumentScanEvent::where('workspace_id', $this->workspace->workspace_id)->delete();

        DocumentScanEvent::create([
            'workspace_id' => $this->workspace->workspace_id,
            'status' => 'uploading',
            'message' => 'Uploading photo…',
            'uploaded_by' => Auth::id(),
        ]);

        $this->dispatch('scan-status-changed')->to(\App\Livewire\Admin\ScanStatusBar::class);
        $this->dispatch('scan-queued')->to(\App\Livewire\Admin\ScanStatusBar::class);

        if ($this->workspace->reference_image_path) {
            Storage::disk('public')->delete($this->workspace->reference_image_path);
        }
        if ($this->workspace->supporting_file_path) {
            Storage::disk('public')->delete($this->workspace->supporting_file_path);
        }

        $path = $this->supportingFile->store('supporting-documents', 'public');
        $referenceImagePath = $this->storeReferenceImage($this->referenceImageBase64);

        $this->workspace->update([
            'supporting_file_path' => $path,
            'reference_image_path' => $referenceImagePath,
            'supporting_uploaded_at' => now(),
            'processing' => true,
            'analysis_status' => 'pending',
            'rejection_reason' => null,
        ]);

        DocumentScanEvent::create([
            'workspace_id' => $this->workspace->workspace_id,
            'status' => 'queued',
            'message' => 'Waiting in line for AI review…',
            'uploaded_by' => Auth::id(),
        ]);

        \App\Jobs\ProcessDocumentAnalysis::dispatch($this->workspace->workspace_id);

        $this->dispatch('scan-queued')->to(\App\Livewire\Admin\ScanStatusBar::class);

        $this->supportingFile = null;
        $this->referenceImageBase64 = null;
        $this->workspace->refresh();
    }
    private function storeReferenceImage(?string $base64): ?string
    {
        if (! $base64 || ! str_starts_with($base64, 'data:image')) return null;
        $parts = explode(',', $base64, 2);
        if (count($parts) !== 2) return null;
        $binary = base64_decode($parts[1]);
        if ($binary === false) return null;
        $path = 'reference-previews/' . $this->workspace->workspace_id . '-' . uniqid() . '.jpg';
        Storage::disk('public')->put($path, $binary);
        return $path;
    }

    public function render()
    {
        return view('livewire.admin.document-uploads.show')
            ->layout('layouts.app', ['title' => 'Document Upload — ' . ($this->workspace->template?->name ?? 'Document')]);
    }
}