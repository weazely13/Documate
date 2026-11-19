<?php

namespace App\Livewire\Support;

use App\Models\FeedbackReport;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Users send feedback and follow the office's replies. */
class FeedbackCenter extends Component
{
    use WithFileUploads;

    private const MAX_PER_HOUR = 5;

    public string $category = '';
    public ?int $rating = null;
    public string $subject = '';
    public string $message = '';
    public string $pageUrl = '';
    public $attachment = null;

    public ?int $openId = null;

    public function mount(): void
    {
        abort_if(auth()->user()->isAdmin(), 403);

        // The page they came from, so the office can see where the feedback was sent from
        $previous = url()->previous();
        if ($previous && $previous !== url()->current() && Str::startsWith($previous, url('/'))) {
            $this->pageUrl = $previous;
        }

        $this->openId = (int) request()->query('report') ?: null;
    }

    public function setRating(int $stars): void
    {
        $this->rating = max(1, min(5, $stars));
    }

    protected function rules(): array
    {
        return [
            'category' => ['required', Rule::in(array_keys(FeedbackReport::CATEGORIES))],
            'rating' => ['required', 'integer', 'between:1,5'],
            'subject' => ['required', 'string', 'min:5', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'pageUrl' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    protected function messages(): array
    {
        return [
            'category.required' => 'Pick the topic that fits best.',
            'rating.required' => 'Tap a star to rate your experience.',
            'attachment.image' => 'The attachment must be a screenshot or photo.',
            'attachment.max' => 'The image must be 5 MB or smaller.',
        ];
    }

    public function submit(): void
    {
        $data = $this->validate();
        $user = auth()->user();

        $key = 'feedback:' . $user->id;
        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_HOUR)) {
            $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
            $this->addError('message', "You've sent several reports in the last hour. Please try again in {$minutes} " . Str::plural('minute', $minutes) . '.');

            return;
        }
        RateLimiter::hit($key, 3600);

        $report = FeedbackReport::create([
            'user_id' => $user->id,
            'category' => $data['category'],
            'rating' => $data['rating'],
            'subject' => trim($data['subject']),
            'message' => trim($data['message']),
            'page_url' => $data['pageUrl'] ?: null,
            'attachment_path' => $this->attachment?->store('feedback-attachments', 'local'),
            'user_agent' => Str::limit((string) request()->userAgent(), 495),
            'status' => 'new',
        ]);

        $this->notifyAdmins($report, $user);

        $this->reset(['category', 'rating', 'subject', 'message', 'attachment']);
        $this->openId = $report->id;

        $this->dispatch('toast', type: 'success',
            title: 'Thanks for your feedback',
            message: "Reference {$report->reference()}. The office will reply here.");
    }

    public function toggle(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
    }

    private function notifyAdmins(FeedbackReport $report, User $from): void
    {
        $admins = User::query()->whereHas('role', fn ($q) => $q->where('role_name', 'Admin'))->get();
        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new WorkflowNotification(
            title: 'New feedback',
            message: 'sent "' . Str::limit($report->subject, 60) . '"',
            url: route('admin.feedback', ['report' => $report->id]),
            icon: 'bx-message-square-dots',
            color: 'text-blue-600 bg-blue-100',
            actor: $from,
            namePrefix: true,
        ));
    }

    public function render()
    {
        $reports = FeedbackReport::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(50)
            ->get();

        return view('livewire.support.feedback-center', [
            'reports' => $reports,
            'categories' => FeedbackReport::CATEGORIES,
        ])->layout('layouts.app', ['title' => 'Feedback']);
    }
}
