<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AppointmentStatusLog;
use App\Observers\AppointmentStatusLogObserver;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use App\Listeners\ClearDocumentScanEventsOnLogout;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        AppointmentStatusLog::observe(AppointmentStatusLogObserver::class);
        Event::listen(Logout::class, ClearDocumentScanEventsOnLogout::class);
    }
}
