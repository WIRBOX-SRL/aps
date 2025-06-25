<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CheckSubscriptionLimits
{
    public function handle(Request $request, Closure $next, $resource = null)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Check if the user has an active subscription
        if (!$user->hasActiveSubscription()) {
            Notification::make()
                ->title('No active subscription')
                ->body('You need an active subscription to perform this action.')
                ->danger()
                ->send();

            return redirect()->back();
        }

        // Check the limits based on the accessed resource
        switch ($resource) {
            case 'users':
                if (!$user->canCreateMoreUsers()) {
                    Notification::make()
                        ->title('User limit reached')
                        ->body('You have reached your user limit.')
                        ->danger()
                        ->send();
                    return redirect()->back();
                }
                break;

            case 'announcements':
                if (!$user->canCreateMorePosts()) {
                    Notification::make()
                        ->title('Post limit reached')
                        ->body('You have reached your post limit.')
                        ->danger()
                        ->send();
                    return redirect()->back();
                }
                break;
        }

        return $next($request);
    }
}
