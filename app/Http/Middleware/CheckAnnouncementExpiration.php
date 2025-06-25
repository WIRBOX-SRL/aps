<?php

namespace App\Http\Middleware;

use App\Models\Announcement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnnouncementExpiration
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Check for expired announcements and expire them automatically
            $expiredAnnouncements = Announcement::where('status', 'published')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->get();

            // Debug: Check what type of data we're getting
            if (!is_object($expiredAnnouncements) || !method_exists($expiredAnnouncements, 'each')) {
                \Log::error('CheckAnnouncementExpiration: $expiredAnnouncements is not a collection', [
                    'type' => gettype($expiredAnnouncements),
                    'value' => $expiredAnnouncements,
                    'class' => is_object($expiredAnnouncements) ? get_class($expiredAnnouncements) : 'not an object',
                ]);
                return $next($request);
            }

            // Additional check to ensure it's a collection
            if (!$expiredAnnouncements instanceof \Illuminate\Database\Eloquent\Collection) {
                \Log::error('CheckAnnouncementExpiration: $expiredAnnouncements is not an Eloquent Collection', [
                    'type' => gettype($expiredAnnouncements),
                    'class' => get_class($expiredAnnouncements),
                    'value' => $expiredAnnouncements,
                ]);
                return $next($request);
            }

            foreach ($expiredAnnouncements as $announcement) {
                $announcement->expire();
            }
        } catch (\Exception $e) {
            \Log::error('CheckAnnouncementExpiration: Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $next($request);
    }
}
