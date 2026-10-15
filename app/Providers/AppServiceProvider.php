<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AppointmentStatusLog;
use App\Observers\AppointmentStatusLogObserver;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use App\Listeners\ClearDocumentScanEventsOnLogout;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Bridge\Brevo\Transport\BrevoTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;


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
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
        Mail::extend('brevo', function () {
            return (new BrevoTransportFactory)->create(
                new Dsn('brevo+api', 'default', config('services.brevo.key'))
            );
        });
    }
    
}
