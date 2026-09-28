<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationBell extends Component
{
    // How many rows to show in each section. "See more" bumps these up.
    public int $todayLimit = 3;
    public int $earlierLimit = 5;

    public function mount()
    {
        abort_if(auth()->user()->account_status !== 'active', 403);
    }

    public function getTodayNotificationsProperty()
    {
        return Auth::user()->notifications()
            ->whereDate('created_at', today())
            ->latest()
            ->limit($this->todayLimit)
            ->get();
    }

    public function getHasMoreTodayProperty(): bool
    {
        return Auth::user()->notifications()
            ->whereDate('created_at', today())
            ->count() > $this->todayLimit;
    }

    public function getEarlierNotificationsProperty()
    {
        return Auth::user()->notifications()
            ->whereDate('created_at', '<', today())
            ->latest()
            ->limit($this->earlierLimit)
            ->get();
    }

    public function getHasMoreEarlierProperty(): bool
    {
        return Auth::user()->notifications()
            ->whereDate('created_at', '<', today())
            ->count() > $this->earlierLimit;
    }

    // Panel grows taller once either section has been expanded past its default.
    public function getIsExpandedProperty(): bool
    {
        return $this->todayLimit > 3 || $this->earlierLimit > 5;
    }

    public function getUnreadCountProperty()
    {
        return Auth::user()->unreadNotifications()->count();
    }

    public function showMoreToday(): void
    {
        $this->todayLimit += 10;
    }

    public function showAllPrevious(): void
    {
        $this->earlierLimit = Auth::user()->notifications()
            ->whereDate('created_at', '<', today())
            ->count();
    }

    public function openNotification(string $notificationId)
    {
        $notification = Auth::user()->notifications()->where('id', $notificationId)->first();

        if (!$notification) {
            return;
        }

        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        // 1. Handle Appointment Redirects
        $appointmentId = $notification->data['appointment_id'] ?? null;

        if ($appointmentId) {
            $routeName = Auth::user()->role->role_name === 'Admin'
                ? 'admin.appointments.show'
                : 'student.appointments.show';

            return $this->redirect(route($routeName, $appointmentId), navigate: true);
        }

        // 2. Handle Clearance Status Redirects (or any notification with a 'url' key)
        $url = $notification->data['url'] ?? null;

        if ($url) {
            return $this->redirect($url, navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}