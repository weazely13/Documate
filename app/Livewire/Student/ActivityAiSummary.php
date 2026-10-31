<?php

namespace App\Livewire\Student;

use App\Exceptions\GeminiQuotaExceededException;
use App\Services\ActivitySummaryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * The AI summary card on the Activity summary page. Loads after the page (lazy), so the page
 * never waits on the API. The facts come from the page itself; see Dashboard::aiFacts().
 */
#[Lazy]
class ActivityAiSummary extends Component
{
    #[Locked]
    public array $facts = [];

    public ?array $result = null;

    public ?string $error = null;

    public function mount(array $facts): void
    {
        $this->facts = $facts;
        $this->load(false);
    }

    public function refresh(): void
    {
        $key = 'activity-summary-refresh:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->error = 'You can refresh the summary again in ' . ceil(RateLimiter::availableIn($key) / 60) . ' minute(s).';

            return;
        }
        RateLimiter::hit($key, 600);

        $this->load(true);
    }

    private function load(bool $fresh): void
    {
        $this->error = null;

        try {
            $this->result = app(ActivitySummaryService::class)->summarize((int) Auth::id(), $this->facts, $fresh);
        } catch (GeminiQuotaExceededException) {
            $this->error = 'The AI summary is busy right now. Please try again in a few minutes.';
        } catch (\Throwable $e) {
            report($e);
            $this->error = 'The AI summary is not available right now.';
        }
    }

    public function placeholder()
    {
        return view('livewire.student.activity-ai-summary', ['loading' => true]);
    }

    public function render()
    {
        return view('livewire.student.activity-ai-summary', ['loading' => false]);
    }
}
