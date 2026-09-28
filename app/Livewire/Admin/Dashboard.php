<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    /**
     * Dashboard KPIs.
     *
     * These values are intentionally kept focused on the
     * administrative overview of the DocuMate system.
     */
    public function getKpisProperty(): array
    {
        $totalStudents = DB::table('users')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->whereIn('roles.role_name', ['Student', 'Officer'])
            ->count();

        $activeAccounts = DB::table('users')
            ->where('account_status', 'active')
            ->count();

        $pendingVerification = DB::table('student_verifications')
            ->where('status', 'pending')
            ->count();

        $pendingTransactions = DB::table('student_document_workspaces')
            ->where('status', 'pending')
            ->count();

        $todaysAppointments = DB::table('appointments')
            ->whereDate('appointment_date', today())
            ->count();

        $pendingAppointmentReview = DB::table('appointments')
            ->where('status', 'pending')
            ->count();

        $clearanceTotal = DB::table('clearance_statuses')
            ->count();

        $clearedCount = DB::table('clearance_statuses')
            ->where('status', 'Cleared')
            ->count();

        return [
            'total_students' => $totalStudents,
            'active_accounts' => $activeAccounts,
            'pending_verification' => $pendingVerification,
            'pending_transactions' => $pendingTransactions,
            'todays_appointments' => $todaysAppointments,
            'pending_appointment_review' => $pendingAppointmentReview,
            'clearance_rate' => $clearanceTotal > 0
                ? round(($clearedCount / $clearanceTotal) * 100)
                : 0,
        ];
    }

    /**
     * Recent activity across appointments and transactions.
     *
     * This gives the admin a quick view of what has recently
     * happened in the system.
     */
    public function getRecentActivityProperty()
    {
        $appointments = DB::table('appointments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->select(
                DB::raw("'appointment' as type"),
                'a.appointment_id as id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user_name"),
                'a.status',
                'a.created_at'
            )
            ->orderByDesc('a.created_at')
            ->limit(5);

        $transactions = DB::table('student_document_workspaces as w')
            ->join('users as u', 'u.id', '=', 'w.user_id')
            ->select(
                DB::raw("'transaction' as type"),
                'w.workspace_id as id',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user_name"),
                'w.status',
                'w.created_at'
            )
            ->orderByDesc('w.created_at')
            ->limit(5);

        return $appointments
            ->unionAll($transactions)
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();
    }

    /**
     * Quick action data.
     *
     * Keeps the Blade template clean while allowing the
     * Action Center to dynamically reflect current workload.
     */
    public function getActionItemsProperty(): array
    {
        return [
            [
                'label' => 'Pending Verifications',
                'count' => $this->kpis['pending_verification'],
                'description' => 'Student accounts waiting for verification.',
                'route' => 'admin.verification',
                'icon' => 'bx-id-card',
                'tone' => 'amber',
            ],
            [
                'label' => 'Pending Transactions',
                'count' => $this->kpis['pending_transactions'],
                'description' => 'Transactions that still need attention.',
                'route' => 'admin.transactions.index',
                'icon' => 'bx-transfer',
                'tone' => 'blue',
            ],
            [
                'label' => 'Appointment Requests',
                'count' => $this->kpis['pending_appointment_review'],
                'description' => 'Appointments awaiting admin review.',
                'route' => 'admin.appointments.index',
                'icon' => 'bx-calendar-exclamation',
                'tone' => 'red',
            ],
        ];
    }

    /**
     * Today's appointment overview.
     */
    public function getTodaysAppointmentsProperty()
    {
        return DB::table('appointments as a')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->select(
                'a.appointment_id',
                'a.appointment_date',
                'a.status',
                'a.created_at',
                DB::raw("CONCAT(u.first_name, ' ', u.last_name) as user_name")
            )
            ->whereDate('a.appointment_date', today())
            ->orderBy('a.appointment_date')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.admin.dashboard', [
            'kpis' => $this->kpis,
            'recentActivity' => $this->recentActivity,
            'actionItems' => $this->actionItems,
            'todaysAppointments' => $this->todaysAppointments,
        ])->layout('layouts.app', [
            'title' => 'Dashboard',
        ]);
    }
}