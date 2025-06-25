<?php

namespace App\Http\Middleware;

use App\Models\Announcement;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnnouncementIpAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the announcement ID from the route
        $announcementId = $request->route('announcement') ?? $request->route('id');

        if (!$announcementId) {
            return $next($request);
        }

        // Find the announcement
        $announcement = Announcement::find($announcementId);

        if (!$announcement) {
            return $next($request);
        }

        // Get client IP
        $clientIp = $request->ip();

        // Check if IP is allowed
        if (!$announcement->canBeAccessedByIp($clientIp)) {
            abort(403, 'Access denied. Your IP address is not allowed to view this announcement.');
        }

        // Log the IP access
        $announcement->logIpAccess($clientIp);

        return $next($request);
    }
}
