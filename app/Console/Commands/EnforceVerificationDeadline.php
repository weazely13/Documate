<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\StudentVerification;
use App\Models\User;
use App\Notifications\AccountDisabledForNonVerification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class EnforceVerificationDeadline extends Command
{
    protected $signature = 'verification:enforce-deadline';

    protected $description = 'Disable accounts that did not complete verification before the current period ended.';

    protected const MONITORED_ROLES = ['Student', 'Officer'];

    public function handle(): int
    {
        $period = Setting::query()->latest('id')->first();

        if (! $period) {
            $this->info('No verification period has been raised. Nothing to do.');

            return self::SUCCESS;
        }

        if (! $period->isExpired()) {
            $this->info('Current verification period has not ended yet. Nothing to do.');

            return self::SUCCESS;
        }

        $verifiedUserIds = StudentVerification::query()
            ->where('setting_id', $period->id)
            ->where('status', StudentVerification::STATUS_VERIFIED)
            ->pluck('user_id');

        $usersToDisable = User::query()
            ->whereHas('role', fn (Builder $q) => $q->whereIn('role_name', self::MONITORED_ROLES))
            ->where('account_status', 'active')
            ->whereNotIn('id', $verifiedUserIds)
            ->get();

        if ($usersToDisable->isEmpty()) {
            $this->info('Everyone is verified or already inactive. Nothing to do.');

            return self::SUCCESS;
        }

        User::whereIn('id', $usersToDisable->pluck('id'))->update(['account_status' => 'inactive']);

        NotificationFacade::send($usersToDisable, new AccountDisabledForNonVerification($period));

        $this->info("Disabled {$usersToDisable->count()} account(s) for missing verification period #{$period->id}.");

        return self::SUCCESS;
    }
}