<?php

namespace App\Livewire\Admin;

use App\Models\FeedbackReport;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin inbox for feedback and issue reports: a list, and a details panel to reply, note and resolve. */
class FeedbackInbox extends Component
{
    use WithPagination;

    public const PER_PAGE = 15;

    public const TABS = [
        'open' => 'Open',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
        'all' => 'All',
    ];

    #[Url(except: 'open')]
    public string $tab = 'open';

    #[Url(except: '')]
    public string $kind = ''; // '' | feedback | issue

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'report', except: null)]
    public ?int $selectedId = null;

    public string $reply = '';
    public string $note = '';

    public function mount(): void
    {
        if ($this->selectedId) {
            $this->select($this->selectedId);
        }
    }

    public function updatingTab(): void { $this->resetPage(); }
    public function updatingKind(): void { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }

    public function select(int $id): void
    {
        $report = FeedbackReport::find($id);
        if (! $report) {
            $this->selectedId = null;

            return;
        }

        $this->selectedId = $report->id;
        $this->reply = (string) $report->admin_reply;
        $this->note = (string) $report->internal_note;
        $this->resetValidation();
    }

    public function closePanel(): void
    {
        $this->selectedId = null;
    }

    private function selected(): FeedbackReport
    {
        return FeedbackReport::findOrFail($this->selectedId);
    }

    public function markInProgress(): void
    {
        $report = $this->selected();
        $report->update(['status' => 'in_progress', 'handled_by' => auth()->id(), 'resolved_at' => null]);

        $this->dispatch('toast', type: 'success', title: 'Marked in progress', message: $report->reference());
    }

    public function sendReply(): void
    {
        $this->validate(['reply' => ['required', 'string', 'min:2', 'max:3000']], ['reply.required' => 'Write a reply first.']);

        $report = $this->selected();
        $report->update([
            'admin_reply' => trim($this->reply),
            'replied_at' => now(),
            'handled_by' => auth()->id(),
            'status' => $report->status === 'new' ? 'in_progress' : $report->status,
        ]);

        $this->notifyUser($report, 'The OVPSD office replied to "' . Str::limit($report->subject, 50) . '"');
        $this->dispatch('toast', type: 'success', title: 'Reply sent', message: 'The user was notified.');
    }

    public function resolve(): void
    {
        $this->validate(['reply' => ['nullable', 'string', 'max:3000']]);

        $report = $this->selected();
        $changes = ['status' => 'resolved', 'resolved_at' => now(), 'handled_by' => auth()->id()];
        if (trim($this->reply) !== '' && trim($this->reply) !== (string) $report->admin_reply) {
            $changes += ['admin_reply' => trim($this->reply), 'replied_at' => now()];
        }
        $report->update($changes);

        $this->notifyUser($report, ($report->type === 'issue' ? 'Your issue report' : 'Your feedback') . ' "' . Str::limit($report->subject, 50) . '" was marked resolved');
        $this->dispatch('toast', type: 'success', title: 'Resolved', message: $report->reference() . ' moved to Resolved.');
    }

    public function close(): void
    {
        $report = $this->selected();
        $report->update(['status' => 'closed', 'handled_by' => auth()->id()]);

        $this->dispatch('toast', type: 'success', title: 'Closed', message: $report->reference() . ' moved to Closed.');
    }

    public function reopen(): void
    {
        $report = $this->selected();
        $report->update(['status' => 'in_progress', 'resolved_at' => null, 'handled_by' => auth()->id()]);

        $this->dispatch('toast', type: 'success', title: 'Reopened', message: $report->reference() . ' is open again.');
    }

    public function saveNote(): void
    {
        $this->validate(['note' => ['nullable', 'string', 'max:3000']]);

        $report = $this->selected();
        $note = trim($this->note) ?: null;
        if ($note !== $report->internal_note) {
            $report->update(['internal_note' => $note]);
            $this->dispatch('toast', type: 'success', title: 'Note saved', message: 'Only admins can see internal notes.');
        }
    }

    private function notifyUser(FeedbackReport $report, string $message): void
    {
        $report->user?->notify(new WorkflowNotification(
            title: $report->type === 'issue' ? 'Update on your issue report' : 'Reply to your feedback',
            message: $message,
            url: route('support.feedback', ['report' => $report->id]),
            icon: 'bx-reply',
            color: 'text-blue-600 bg-blue-100',
            actor: auth()->user(),
        ));
    }

    private function query()
    {
        return FeedbackReport::query()
            ->when($this->kind !== '', fn ($q) => $q->where('type', $this->kind))
            ->when($this->search !== '', function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn ($w) => $w->where('subject', 'like', $term)
                    ->orWhere('message', 'like', $term)
                    ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('student_number', 'like', $term)));
            });
    }

    private function forTab($query, string $tab)
    {
        return match ($tab) {
            'open' => $query->open(),
            'resolved' => $query->where('status', 'resolved'),
            'closed' => $query->where('status', 'closed'),
            default => $query,
        };
    }

    public function render()
    {
        $counts = collect(self::TABS)->map(fn ($label, $key) => $this->forTab($this->query(), $key)->count());

        $reports = $this->forTab($this->query(), $this->tab)
            ->with('user.role')
            // Open tab: issues that block people first, then newest
            ->when($this->tab === 'open', fn ($q) => $q->orderByRaw("CASE WHEN status = 'new' THEN 0 ELSE 1 END")
                ->orderByRaw("CASE severity WHEN 'high' THEN 0 WHEN 'medium' THEN 1 ELSE 2 END"))
            ->latest()
            ->paginate(self::PER_PAGE);

        $stats = [
            'new' => FeedbackReport::where('status', 'new')->count(),
            'open_issues' => FeedbackReport::open()->where('type', 'issue')->count(),
            'resolved_30' => FeedbackReport::where('status', 'resolved')->where('resolved_at', '>=', now()->subDays(30))->count(),
            'avg_rating' => FeedbackReport::whereNotNull('rating')->where('created_at', '>=', now()->subDays(30))->avg('rating'),
            'ratings' => FeedbackReport::whereNotNull('rating')->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return view('livewire.admin.feedback-inbox', [
            'reports' => $reports,
            'counts' => $counts,
            'tabs' => self::TABS,
            'stats' => $stats,
            'selected' => $this->selectedId ? FeedbackReport::with(['user.role', 'handler'])->find($this->selectedId) : null,
        ])->layout('layouts.app', ['title' => 'Feedback & Issues']);
    }
}
