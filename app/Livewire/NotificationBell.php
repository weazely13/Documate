<?php

namespace App\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

class NotificationBell extends Component
{
    /** Category tabs: key => [label, icon]. Only categories the user has notifications in are shown. */
    public const CATEGORIES = [
        'documents' => ['Documents', 'bx-file'],
        'appointments' => ['Appointments', 'bx-calendar'],
        'clearance' => ['Clearance', 'bx-check-shield'],
        'account' => ['Account', 'bx-user'],
        'feedback' => ['Feedback', 'bx-message-square-dots'],
    ];

    private const ACCOUNT_TYPES = ['account_disabled_non_verification', 'account_status_changed_admin', 'verification_period_opened'];

    // How many rows to show in each section. "See more" bumps these up.
    public int $todayLimit = 3;
    public int $earlierLimit = 5;

    /** all | unread | one of CATEGORIES */
    public string $filter = 'all';

    public function mount()
    {
        abort_if(auth()->user()->account_status !== 'active', 403);
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, ['all', 'unread'], true) || array_key_exists($filter, self::CATEGORIES) ? $filter : 'all';
        $this->todayLimit = 3;
        $this->earlierLimit = 5;
    }

    /** The user's notifications narrowed to one category. */
    private function inCategory($query, string $category)
    {
        return match ($category) {
            'unread' => $query->whereNull('read_at'),
            'appointments' => $query->whereNotNull('data->appointment_id'),
            'clearance' => $query->where('data->type', 'clearance_status_updated'),
            'account' => $query->whereIn('data->type', self::ACCOUNT_TYPES),
            'feedback' => $query->where('data->type', 'workflow')->where('data->url', 'like', '%/feedback%'),
            'documents' => $query->where('data->type', 'workflow')->where(fn ($q) => $q->whereNull('data->url')->orWhere('data->url', 'not like', '%/feedback%')),
            default => $query,
        };
    }

    private function filtered()
    {
        return $this->inCategory(Auth::user()->notifications(), $this->filter);
    }

    /** Which category a notification belongs to, for the chip under it. */
    public static function categoryOf(DatabaseNotification $n): ?string
    {
        $data = $n->data;
        $type = $data['type'] ?? null;

        return match (true) {
            isset($data['appointment_id']) => 'appointments',
            $type === 'clearance_status_updated' => 'clearance',
            in_array($type, self::ACCOUNT_TYPES, true) => 'account',
            $type === 'workflow' && Str::contains((string) ($data['url'] ?? ''), '/feedback') => 'feedback',
            $type === 'workflow' => 'documents',
            default => null,
        };
    }

    /**
     * Avatars were saved as absolute URLs when each notification was sent, so older ones can point at a
     * host that no longer serves them (localhost, an old tunnel). Rebuild storage paths for this host.
     */
    public static function avatarUrl(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        return Str::contains($url, '/storage/') ? asset('storage/' . Str::after($url, '/storage/')) : $url;
    }

    public function getTodayNotificationsProperty()
    {
        return $this->filtered()
            ->whereDate('created_at', today())
            ->latest()
            ->limit($this->todayLimit)
            ->get();
    }

    public function getHasMoreTodayProperty(): bool
    {
        return $this->filtered()
            ->whereDate('created_at', today())
            ->count() > $this->todayLimit;
    }

    public function getEarlierNotificationsProperty()
    {
        return $this->filtered()
            ->whereDate('created_at', '<', today())
            ->latest()
            ->limit($this->earlierLimit)
            ->get();
    }

    public function getHasMoreEarlierProperty(): bool
    {
        return $this->filtered()
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

    /** Tabs to show: All, Unread, then only the categories this user has anything in. */
    public function getTabsProperty(): array
    {
        $tabs = ['all' => ['All', null], 'unread' => ['Unread', $this->unreadCount]];

        foreach (self::CATEGORIES as $key => [$label]) {
            if ($this->inCategory(Auth::user()->notifications(), $key)->exists()) {
                $tabs[$key] = [$label, null];
            }
        }

        return $tabs;
    }

    public function showMoreToday(): void
    {
        $this->todayLimit += 10;
    }

    public function showAllPrevious(): void
    {
        $this->earlierLimit = $this->filtered()
            ->whereDate('created_at', '<', today())
            ->count();
    }

    public function markAllRead(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
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
