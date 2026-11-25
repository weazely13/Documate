<?php

namespace App\Livewire\Admin;

use App\Models\FeedbackReport;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin inbox for feedback: a list, and a details panel to read it, reply (optional) and keep an internal note. */
class FeedbackInbox extends Component
{
    use WithPagination;

    public const PER_PAGE = 50;

    /**
     * The summary cards, which are also the list's filters, in their order: Responses (the ones the office replied to),
     * Inbox (every one), Unread and Ratings (the ones that came with a star rating). Replying is optional.
     */
    public const TABS = [
        'replied' => 'Responses',
        'all' => 'Inbox',
        'unread' => 'Unread',
        'rated' => 'Ratings',
    ];

    #[Url(except: 'all')]
    public string $tab = 'all';

    #[Url(except: '')]
    public string $search = '';

    #[Url(as: 'report', except: null)]
    public ?int $selectedId = null;

    public string $reply = '';
    public string $note = '';

    /** Column-header sorting, as on Workflow review: ascending, then descending, then back to the default order. */
    public string $sortKey = '';
    public string $sortDir = 'asc';

    private const SORTS = ['from', 'subject', 'reply', 'sent'];

    public function sortBy(string $key): void
    {
        if (! in_array($key, self::SORTS, true)) {
            return;
        }

        [$this->sortKey, $this->sortDir] = match (true) {
            $this->sortKey !== $key => [$key, 'asc'],
            $this->sortDir === 'asc' => [$key, 'desc'],
            default => ['', 'asc'],
        };

        $this->resetPage();
    }

    /** The picked column's order, or null for the default (unread first, then newest). */
    private function applySort($query)
    {
        $dir = $this->sortDir === 'desc' ? 'desc' : 'asc';

        return match ($this->sortKey) {
            'from' => $query->orderBy(
                \App\Models\User::withTrashed()->select('first_name')->whereColumn('users.id', 'feedback_reports.user_id'), $dir
            )->orderBy(
                \App\Models\User::withTrashed()->select('last_name')->whereColumn('users.id', 'feedback_reports.user_id'), $dir
            ),
            'subject' => $query->orderBy('subject', $dir),
            // Not replied first when ascending; replied ones by when
            'reply' => $query->orderByRaw("CASE WHEN replied_at IS NULL THEN 0 ELSE 1 END {$dir}")->orderBy('replied_at', $dir),
            'sent' => $query->orderBy('created_at', $dir),
            default => null,
        };
    }

    public function mount(): void
    {
        if ($this->selectedId) {
            $this->select($this->selectedId);
        }
    }

    public function updatingTab(): void { $this->resetPage(); }

    /** A summary card: show that feedback; the card already in use goes back to the whole inbox. */
    public function setTab(string $tab): void
    {
        $this->tab = isset(self::TABS[$tab]) && $this->tab !== $tab ? $tab : 'all';
        $this->resetPage();
    }
    public function updatingSearch(): void { $this->resetPage(); }

    public function select(int $id): void
    {
        $report = FeedbackReport::find($id);
        if (! $report) {
            $this->selectedId = null;

            return;
        }

        // Opening it marks it read (a plain update: reading is not an audited change)
        if ($report->isUnread()) {
            FeedbackReport::whereKey($report->id)->update(['status' => FeedbackReport::READ]);
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

    public function sendReply(): void
    {
        $this->validate(['reply' => ['required', 'string', 'min:2', 'max:3000']], ['reply.required' => 'Write a reply first.']);

        $report = $this->selected();
        $report->update([
            'admin_reply' => trim($this->reply),
            'replied_at' => now(),
            'handled_by' => auth()->id(),
        ]);

        $this->notifyUser($report, 'The OVPSD office replied to "' . Str::limit($report->subject, 50) . '"');
        $this->dispatch('toast', type: 'success', title: 'Reply sent', message: 'The user was notified.');
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
            title: 'Reply to your feedback',
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
            'unread' => $query->unread(),
            'replied' => $query->whereNotNull('replied_at'),
            'rated' => $query->whereNotNull('rating'),
            default => $query,
        };
    }

    public function render()
    {
        if (! isset(self::TABS[$this->tab])) {
            $this->tab = 'all';
        }

        $counts = collect(self::TABS)->map(fn ($label, $key) => $this->forTab($this->query(), $key)->count());

        $reports = $this->forTab($this->query(), $this->tab)->with('user.role');

        if ($this->applySort($reports) === null) {
            // Unread first, then newest
            $reports->orderByRaw("CASE WHEN status = 'new' THEN 0 ELSE 1 END");
        }

        $reports = $reports->latest('id')->paginate(self::PER_PAGE);

        // The Ratings card shows the average of the ratings it lists (the search applies, as it does to the counts).
        $stats = [
            'avg_rating' => $this->forTab($this->query(), 'rated')->avg('rating'),
        ];

        return view('livewire.admin.feedback-inbox', [
            'reports' => $reports,
            'counts' => $counts,
            'tabs' => self::TABS,
            'stats' => $stats,
            'selected' => $this->selectedId ? FeedbackReport::with(['user.role', 'handler'])->find($this->selectedId) : null,
        ])->layout('layouts.app', ['title' => 'Feedback']);
    }
}
