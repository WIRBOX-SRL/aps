<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;

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
        // Set the disk for dynamic avatar, depending on user/admin
        if (app()->runningInConsole()) {
            return;
        }
        Event::listen('filament.serving', function () {
            $user = Auth::user();
            if ($user && method_exists($user, 'getAdminForUpload') && $user->getAdminForUpload()->hasCloudinaryConfigured()) {
                config(['filament-edit-profile.disk' => 'cloudinary']);
            } else {
                config(['filament-edit-profile.disk' => 'public']);
            }
        });
    }
}
