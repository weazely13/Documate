<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\AppointmentReviewAgent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReviewAppointmentSubmission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 15;

    public function __construct(public Appointment $appointment)
    {
    }

    public function handle(AppointmentReviewAgent $agent): void
    {
        $agent->review($this->appointment);
    }
}