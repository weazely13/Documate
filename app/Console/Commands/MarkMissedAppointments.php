<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Notifications\AppointmentMissed;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarkMissedAppointments extends Command
{
    protected $signature = 'appointments:mark-missed';
    protected $description = 'Marks approved appointments as missed once their session window has lapsed';

    public function handle(): void
    {
        $now = Carbon::now();

        $candidates = Appointment::with('user', 'workspace')
            ->where('status', 'approved')
            ->where('appointment_date', '<=', $now->toDateString())
            ->get();

        $missedCount = 0;

        foreach ($candidates as $appointment) {
            if ($this->sessionHasLapsed($appointment, $now)) {
                $appointment->update(['status' => 'missed']);
                $appointment->logStatus('missed');
                $appointment->workspace?->increment('missed_count');
                $appointment->user->notify(new AppointmentMissed($appointment));
                $missedCount++;
            }
        }

        $this->info("Marked {$missedCount} appointment(s) as missed.");
    }

    private function sessionHasLapsed(Appointment $appointment, Carbon $now): bool
    {
        if ($appointment->appointment_date->lt($now->toDateString())) {
            return true;
        }

        [, $endHour] = $appointment->session === 'morning' ? [8, 12] : [13, 17];
        $sessionEnd = $appointment->appointment_date->copy()->setTime($endHour, 0);

        return $now->greaterThanOrEqualTo($sessionEnd);
    }
}