<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifiedStudent
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Admin bypass
        if ($user->role->role_name === 'Admin') {
            return $next($request);
        }

        // account_status is only ever changed by: the scheduled
        // verification:enforce-deadline command, a successful
        // VerifyStudent::verify() call, or an admin's manual toggle.
        // This middleware just reacts to whatever it currently is.
        if ($user->account_status === 'inactive') {
            if (!$request->routeIs('verify.page')) {
                return redirect()->route('verify.page');
            }
        }

        return $next($request);
    }
}