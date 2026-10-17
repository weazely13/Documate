<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AccountStatusChangedByAdmin extends Notification
{
    use Queueable;

    /**
     * @param string    $newStatus  'active' | 'inactive' (etc.)
     * @param User|null $admin      The admin who made the change, if known.
     *                              Pass it in from the controller/action that fires this
     *                              notification, e.g. new AccountStatusChangedByAdmin('active', auth()->user())
     */
    public function __construct(public string $newStatus, public ?User $admin = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $isActive = $this->newStatus === 'active';

        // Name is bolded separately in the UI, so keep it out of the message text itself.
        $message = $isActive
            ? 'reactivated your account.'
            : 'deactivated your account.';

        return [
            'type' => 'account_status_changed_admin',
            'status' => $this->newStatus,

            'icon' => $isActive ? 'bx-user-check' : 'bx-user-x',
            'color' => $isActive
                ? 'text-emerald-600 bg-emerald-100'
                : 'text-rose-600 bg-rose-100',

            'actor_name' => $this->admin?->first_name,
            'actor_avatar' => $this->admin?->profile_picture
                ? asset('storage/' . $this->admin->profile_picture)
                : null,

            // Falls back to "An administrator has ..." if no admin was passed in.
            'message' => $this->admin
                ? $message
                : 'An administrator has ' . ($isActive ? 'reactivated' : 'deactivated') . ' your account.',
        ];
    }
}