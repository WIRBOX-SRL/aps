<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\Response;

class CheckUserSuspended
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->is_suspended) {
            // Disconnect suspended user
            Auth::logout();

            // Notificare pentru user
            Notification::make()
                ->title('Account suspended')
                ->body('Your account has been suspended by the administrator.')
                ->danger()
                ->persistent()
                ->send();

            // Redirect la login cu mesaj
            return redirect()->route('filament.admin.auth.login')
                ->with('error', 'Your account has been suspended. Please contact the administrator for assistance.');
        }

        return $next($request);
    }
}
