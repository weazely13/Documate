<?php

namespace App\Livewire\Admin\DocumentUploads;

use App\Models\StudentDocumentWorkspace;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class DocumentUploadsIndex extends Component
{
    public string $search = '';
    public string $filter = 'needs_action'; // needs_action | verified | all
    public string $dateFrom = '';
    public string $dateTo = '';

    public function clearDateFilter(): void
    {
        $this->dateFrom = '';
        $this->dateTo = '';
    }

    protected function students(): Collection
    {
        return StudentDocumentWorkspace::query()
            ->whereHas('appointments', fn ($q) => $q->where('status', 'attended'))
            ->with(['user', 'template', 'appointments'])
            ->get()
            ->map(fn (StudentDocumentWorkspace $workspace) => [
                'workspace_id' => $workspace->workspace_id,
                'user_id' => $workspace->user_id,
                'student_name' => $this->fullName($workspace->user),
                'student_number' => $workspace->user?->student_number ?: 'N/A',
                'type' => $workspace->template?->name ?: 'Untitled template',
                'attended_at' => optional($workspace->appointments->firstWhere('status', 'attended'))?->updated_at,
                'doc_status' => $workspace->documentVerificationStatus(),
            ])
            ->filter(fn (array $r) => match ($this->filter) {
                'needs_action' => in_array($r['doc_status'], ['Needs Upload', 'Needs Re-upload']),
                'verified' => $r['doc_status'] === 'Verified',
                default => true,
            })
            ->filter(function (array $r) {
                if ($this->dateFrom === '' && $this->dateTo === '') {
                    return true;
                }
                if (! $r['attended_at']) {
                    return false;
                }
                $attendedDate = $r['attended_at']->format('Y-m-d');
                if ($this->dateFrom !== '' && $attendedDate < $this->dateFrom) return false;
                if ($this->dateTo !== '' && $attendedDate > $this->dateTo) return false;
                return true;
            })
            ->filter(fn (array $r) => $this->search === '' || str_contains(
                strtolower($r['student_name'] . ' ' . $r['student_number'] . ' ' . $r['type']),
                strtolower($this->search)
            ))
            // ---- group: one row per student ----
            ->groupBy('user_id')
            ->map(function (Collection $forms) {
                $forms = $forms
                    ->sortByDesc(fn (array $r) => $r['attended_at']?->timestamp ?? 0)
                    ->values();
                $first = $forms->first(); // latest form

                return [
                    'user_id' => $first['user_id'],
                    'student_name' => $first['student_name'],
                    'student_number' => $first['student_number'],
                    'forms' => $forms,
                    'total' => $forms->count(),
                    'needs_action' => $forms->whereIn('doc_status', ['Needs Upload', 'Needs Re-upload'])->count(),
                    'all_verified' => $forms->every(fn ($f) => $f['doc_status'] === 'Verified'),
                    'latest_attended' => $first['attended_at'],
                ];
            })
            ->sortByDesc(fn (array $s) => $s['latest_attended']?->timestamp ?? 0)
            ->values();
    }

    private function fullName(?User $user): string
    {
        if (! $user) return 'Unknown student';
        return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
            $user->first_name, $user->middle_name, $user->last_name, $user->suffix,
        ])))) ?: ('User #' . $user->id);
    }

    public function render()
    {
        return view('livewire.admin.document-uploads.index', [
            'students' => $this->students(),
        ])->layout('layouts.app', ['title' => 'Document Uploads']);
    }
}