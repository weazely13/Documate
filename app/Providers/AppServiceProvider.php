<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\AppointmentStatusLog;
use App\Observers\AppointmentStatusLogObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Support\Str;
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

        // Audit trail: sign-ins and sign-outs
        Event::listen(Login::class, fn (Login $e) => $e->user instanceof User
            ? Audit::record('login', 'Authentication', Audit::userName($e->user) . ' signed in' . ($e->remember ? ' (remember me)' : ''), $e->user, actor: $e->user)
            : null);
        Event::listen(Logout::class, fn (Logout $e) => $e->user instanceof User
            ? Audit::record('logout', 'Authentication', Audit::userName($e->user) . ' signed out', $e->user, actor: $e->user)
            : null);
        Event::listen(Failed::class, function (Failed $e) {
            $email = Str::limit((string) ($e->credentials['email'] ?? $e->credentials['student_number'] ?? 'unknown'), 120);
            Audit::record('login_failed', 'Authentication', "Failed sign-in attempt for \"{$email}\"",
                $e->user instanceof User ? $e->user : null, actor: null);
        });
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
