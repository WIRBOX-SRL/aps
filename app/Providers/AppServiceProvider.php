<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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

        // Listen for various events to set up Cloudinary configuration
        Event::listen(['filament.serving', 'livewire.update', 'livewire.call'], function () {
            $this->configureCloudinaryDynamically();
        });
    }

    private function configureCloudinaryDynamically(): void
    {
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();
        if ($user && method_exists($user, 'getAdminForUpload') && $user->getAdminForUpload()->hasCloudinaryConfigured()) {
            // Get the user's Cloudinary settings
            $adminForUpload = $user->getAdminForUpload();
            $cloudSettings = $adminForUpload->getCloudSettings();

            // Dynamically configure the Cloudinary settings
            config([
                'cloudinary.cloud_url' => 'cloudinary://' . $cloudSettings['cloudinary_api_key'] . ':' . $cloudSettings['cloudinary_api_secret'] . '@' . $cloudSettings['cloudinary_cloud_name'],
                'cloudinary.cloud' => $cloudSettings['cloudinary_cloud_name'],
                'cloudinary.key' => $cloudSettings['cloudinary_api_key'],
                'cloudinary.secret' => $cloudSettings['cloudinary_api_secret'],
                'filesystems.disks.cloudinary.cloud' => $cloudSettings['cloudinary_cloud_name'],
                'filesystems.disks.cloudinary.key' => $cloudSettings['cloudinary_api_key'],
                'filesystems.disks.cloudinary.secret' => $cloudSettings['cloudinary_api_secret'],
                'filament-edit-profile.disk' => 'cloudinary'
            ]);

            // Purge the disk cache to use the new configuration
            Storage::forgetDisk('cloudinary');
        } else {
            config(['filament-edit-profile.disk' => 'public']);
        }
    }
}
