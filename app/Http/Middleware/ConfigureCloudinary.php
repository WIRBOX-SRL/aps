<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ConfigureCloudinary
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
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
                    'filament-edit-profile.disk' => 'cloudinary',
                ]);

                // Purge the disk cache to use the new configuration
                Storage::forgetDisk('cloudinary');
            }
        }

        return $next($request);
    }
}
