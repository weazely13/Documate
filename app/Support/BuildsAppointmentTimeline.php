<?php

namespace App\Support;

trait BuildsAppointmentTimeline
{
    protected function buildRawTimeline(\App\Models\Appointment $appointment): \Illuminate\Support\Collection
    {
        $items = collect();

        $items->push([
            'title' => 'Appointment requested',
            'date' => $appointment->created_at,
            'tone' => 'blue',
        ]);

        foreach ($appointment->statusLogs as $log) {
            $items->push([
                'title' => match ($log->status) {
                    'approved' => 'Approved' . ($log->changedBy ? ' by ' . $log->changedBy->first_name : ''),
                    'rejected' => 'Rejected',
                    'attended' => 'Visit completed',
                    'missed' => 'Marked as missed',
                    'retracted' => 'Retracted',
                    'pending' => $log->note ?: 'Status set back to pending',
                    default => ucfirst($log->status),
                },
                'date' => $log->created_at,
                'tone' => match ($log->status) {
                    'approved', 'attended' => 'green',
                    'rejected', 'missed' => 'red',
                    'pending' => 'amber',
                    default => 'default',
                },
                'note' => $log->status === 'rejected' || $log->status === 'attended' ? $log->note : ($log->status === 'pending' ? null : $log->note),
            ]);
        }

        foreach ($appointment->reschedules as $log) {
            $items->push([
                'title' => 'Rescheduled by office — moved to ' . $log->new_date->format('F j, Y') . ' (' . ucfirst($log->new_session) . ')',
                'date' => $log->created_at,
                'tone' => 'amber',
                'note' => $log->reason ?: 'There is no reason for reschedule indicated.',
            ]);
        }

        foreach ($appointment->reapplications as $log) {
            $items->push([
                'title' => 'Missed appointment on ' . $log->old_date->format('F j, Y') . ' — reapplied for ' . $log->new_date->format('F j, Y') . ' (' . ucfirst($log->new_session) . ')',
                'date' => $log->created_at,
                'tone' => 'red',
            ]);
        }

        return $items->sortBy('date')->values();
    }

    protected function buildTimeline(\App\Models\Appointment $appointment): array
    {
        return $this->buildRawTimeline($appointment)->map(fn ($item) => [
            'title' => $item['title'],
            'date' => $item['date'] ? $item['date']->format('F j, Y g:i A') : 'Pending',
            'tone' => $item['tone'],
            'note' => $item['note'] ?? null,
        ])->all();
    }
}