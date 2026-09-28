<?php

namespace App\Livewire\Student;

use App\Livewire\Student\Concerns\BuildsClearanceStatusViewData;
use App\Models\StudentDocumentWorkspace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    use BuildsClearanceStatusViewData;

    /**
     * Get all dashboard statistics and recent activity
     */
    protected function studentStats(int $userId): array
    {
        /*
        |--------------------------------------------------------------------------
        | DOCUMENT TRANSACTIONS
        |--------------------------------------------------------------------------
        */

        $transactions = DB::table('student_document_workspaces')
            ->where('user_id', $userId)
            ->selectRaw('count(*) as total')
            ->selectRaw("
                sum(
                    case
                        when status = 'completed'
                        then 1
                        else 0
                    end
                ) as completed
            ")
            ->selectRaw("
                sum(
                    case
                        when status = 'pending'
                        then 1
                        else 0
                    end
                ) as pending
            ")
            ->first();


        /*
        |--------------------------------------------------------------------------
        | APPOINTMENTS
        |--------------------------------------------------------------------------
        */

        $appointments = DB::table('appointments')
            ->where('user_id', $userId)
            ->selectRaw('count(*) as total')
            ->selectRaw("
                sum(
                    case
                        when status = 'pending'
                        then 1
                        else 0
                    end
                ) as pending
            ")
            ->selectRaw("
                sum(
                    case
                        when status = 'approved'
                        and appointment_date >= curdate()
                        then 1
                        else 0
                    end
                ) as upcoming
            ")
            ->selectRaw("
                sum(
                    case
                        when status = 'attended'
                        then 1
                        else 0
                    end
                ) as attended
            ")
            ->selectRaw("
                sum(
                    case
                        when status = 'missed'
                        then 1
                        else 0
                    end
                ) as missed
            ")
            ->first();


        /*
        |--------------------------------------------------------------------------
        | NEXT APPOINTMENT
        |--------------------------------------------------------------------------
        */

        $nextAppointment = DB::table('appointments')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('session')
            ->first();


        /*
        |--------------------------------------------------------------------------
        | UPCOMING APPOINTMENTS
        |--------------------------------------------------------------------------
        */

        $upcomingAppointments = DB::table('appointments')
            ->where('user_id', $userId)
            ->where('status', 'approved')
            ->where('appointment_date', '>=', now()->toDateString())
            ->orderBy('appointment_date')
            ->orderBy('session')
            ->limit(4)
            ->get();


        /*
        |--------------------------------------------------------------------------
        | LAST UPDATED / CREATED DOCUMENT
        |--------------------------------------------------------------------------
        |
        | This is the document that will appear inside the dashboard's
        | "Recent Document" card.
        |
        | We use the same StudentDocumentWorkspace model and template
        | relationship used by the Documents page.
        |
        */

        $latestDocument = StudentDocumentWorkspace::query()
            ->with('template')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->first();


        /*
        |--------------------------------------------------------------------------
        | RETURN DASHBOARD DATA
        |--------------------------------------------------------------------------
        */

        return [

            'transactions' => [
                'total' => (int) ($transactions->total ?? 0),
                'completed' => (int) ($transactions->completed ?? 0),
                'pending' => (int) ($transactions->pending ?? 0),
            ],

            'appointments' => [
                'total' => (int) ($appointments->total ?? 0),
                'pending' => (int) ($appointments->pending ?? 0),
                'upcoming' => (int) ($appointments->upcoming ?? 0),
                'attended' => (int) ($appointments->attended ?? 0),
                'missed' => (int) ($appointments->missed ?? 0),
            ],

            'next_appointment' => $nextAppointment,

            'upcoming_appointments' => $upcomingAppointments,

            /*
             * Latest document transaction.
             */
            'latest_document' => $latestDocument,
        ];
    }


    /**
     * Render the student dashboard.
     */
    public function render()
    {
        $user = $this->loadStudentWithClearance(
            (int) Auth::id()
        );

        $fullName = $this->fullName($user);

        return view('livewire.student.dashboard', [

            'user' => $user,

            'fullName' => $fullName,

            'formattedYearLevel' => $this->formatYearLevel(
                (string) $user->year_level
            ),

            'currentClearanceStatus' => $this->buildCurrentClearanceStatus(
                $user
            ),

            'stats' => $this->studentStats(
                (int) Auth::id()
            ),

        ])->layout(
            'layouts.app',
            ['title' => 'Student Dashboard']
        );
    }
}