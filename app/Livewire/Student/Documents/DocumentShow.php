<?php

namespace App\Livewire\Student\Documents;

use App\Models\StudentDocumentWorkspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Support\BuildsAppointmentTimeline;
use App\Support\BuildsTemplateFieldArray;
use Illuminate\Support\Str;

class DocumentShow extends Component
{
    use BuildsAppointmentTimeline, BuildsTemplateFieldArray;

    public StudentDocumentWorkspace $workspace;

    public function mount(StudentDocumentWorkspace $workspace): void
    {
        abort_unless($workspace->user_id === Auth::id(), 403);
        $this->workspace = $workspace->load('template.currentVersion.fields');
    }

   #[Computed]
    public function fields(): array
    {
        $fields = $this->workspace->template?->currentVersion?->fields;
        if (! $fields) return [];

        return $fields
            ->sortBy([['y_position', 'asc'], ['x_position', 'asc']])
            ->map(function ($f) {
                $name = $f->name ? Str::slug($f->name, '_') : ('field_' . $f->field_id);

                return [
                    'id' => (string) $f->field_id,
                    'name' => $name,
                    'label' => $f->label,
                    'type' => $f->field_type === 'paragraph' ? 'paragraph' : $f->data_type,
                    'x' => (float) $f->x_position, 'y' => (float) $f->y_position,
                    'width' => (float) $f->width, 'height' => (float) $f->height,
                    'font_family' => $f->font_family, 'font_weight' => $f->font_weight,
                    'font_size' => (float) $f->font_size, 'text_color' => $f->text_color,
                    'alignment' => $f->alignment,
                    'line_height' => (float) ($f->line_height ?? 1.3),
                    'letter_spacing' => (float) ($f->letter_spacing ?? 0),
                    'text_case' => $f->text_case ?? 'none',
                    'source_type' => $f->source_type,
                    'system_key' => $f->system_key,
                    'date_mode' => $f->date_mode ?? 'current',
                    'font_style' => $f->font_style ?? 'normal',
                    'max_lines' => $f->max_lines,
                ];
            })->values()->all();
    }

    #[Computed]
    public function systemValues(): array
    {
        $version = $this->workspace->template?->currentVersion;
        $owner = $this->workspace->user ?? Auth::user();

        return ($version && $owner)
            ? app(\App\Services\SystemValueResolver::class)->mapForFields($owner, $version->fields)
            : [];
    }

    private function appointmentStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Appointment pending approval',
            'approved' => 'Appointment approved',
            'rejected' => 'Appointment rejected',
            'attended' => 'Appointment visit completed',
            'missed' => 'Appointment marked as missed',
            default => 'Appointment ' . $status,
        };
    }

    private function appointmentStatusTone(string $status): string
    {
        return match ($status) {
            'approved', 'attended' => 'green',
            'rejected', 'missed' => 'red',
            'pending' => 'amber',
            default => 'default',
        };
    }

    public function deleteWorkspace()
    {
        if ($this->workspace->generated_pdf_path) {
            Storage::disk('public')->delete($this->workspace->generated_pdf_path);
        }
        if ($this->workspace->supporting_file_path) {
            Storage::disk('public')->delete($this->workspace->supporting_file_path);
        }
        if ($this->workspace->reference_image_path) {
            Storage::disk('public')->delete($this->workspace->reference_image_path);
        }

        $this->workspace->delete();

        return $this->redirect(route('student.documents.index'), navigate: true);
    }
    #[Computed]
    public function canvas(): array
    {
        return $this->canvasDimensions($this->workspace->template->currentVersion);
    }

    private function canvasDimensions($version): array
    {
        $orientation = $version->orientation ?? 'portrait';
        $sizes = [
            'A4' => [794, 1123], 'A3' => [1123, 1587],
            'Letter' => [816, 1056], 'Legal' => [816, 1344],
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

    #[Computed]
    public function activeAppointment()
    {
        return $this->workspace->appointments()
            ->whereIn('status', ['pending', 'approved'])
            ->latest()
            ->first();
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

    
    /**
     * Decodes the base64 screenshot captured client-side (via html2canvas) of
     * the student's live document preview, and stores it as a JPEG. This is
     * used as visual ground truth so the VLM can compare the physical photo
     * against exactly what the filled document is supposed to look like.
     */

    #[Computed]
    public function timeline(): array
    {
        $items = collect([
            ['title' => 'Document created', 'date' => $this->workspace->created_at, 'tone' => 'blue', 'section' => 'document'],
        ]);

        if ($this->workspace->last_generated_at) {
            $items->push(['title' => 'PDF generated', 'date' => $this->workspace->last_generated_at, 'tone' => 'blue', 'section' => 'document']);
        }

        // Latest non-retracted appointment only
        $appointment = $this->workspace->appointments()
            ->whereNotIn('status', ['retracted'])
            ->latest()
            ->first();

        if ($appointment) {
            $items->push([
                'title' => $this->appointmentStatusLabel($appointment->status),
                'date' => $appointment->updated_at,
                'tone' => $this->appointmentStatusTone($appointment->status),
                'section' => 'appointment',
            ]);
        }

        if ($this->workspace->supporting_uploaded_at) {
            $items->push([
                'title' => 'Document photo uploaded',
                'date' => $this->workspace->supporting_uploaded_at,
                'tone' => 'amber',
                'section' => 'document',
            ]);
        }

        if ($this->workspace->isProcessing()) {
            $items->push([
                'title' => 'AI review in progress',
                'date' => $this->workspace->supporting_uploaded_at,
                'tone' => 'amber',
                'section' => 'document',
            ]);
        } elseif ($this->workspace->analysis_status === 'rejected' && $this->workspace->analyzed_at) {
            $items->push([
                'title' => 'Document not accepted — see details below',
                'date' => $this->workspace->analyzed_at,
                'tone' => 'red',
                'section' => 'document',
            ]);
        }

        if ($this->workspace->isCompleted()) {
            $items->push(['title' => 'Transaction completed', 'date' => $this->workspace->completed_at, 'tone' => 'green', 'section' => 'document']);
        } elseif (! $appointment) {
            $items->push(['title' => 'Awaiting VPSD visit & document upload', 'date' => null, 'tone' => 'default', 'section' => 'document']);
        }

        return $items->sortBy('date')->values()->map(fn ($item) => [
            'title' => $item['title'],
            'date' => $item['date'] ? $item['date']->format('F j, Y g:i A') : 'Pending',
            'tone' => $item['tone'],
            'section' => $item['section'],
        ])->all();
    }

    public function render()
    {
        return view('livewire.student.documents.document-show')
            ->layout('layouts.app', ['title' => $this->workspace->template?->name ?? 'Document']);
    }
}