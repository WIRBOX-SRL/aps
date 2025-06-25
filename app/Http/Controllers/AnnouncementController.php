<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnnouncementController extends Controller
{
    /**
     * Display the specified announcement.
     */
    public function show(Request $request, $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        // Check if announcement is published
        if (!$announcement->isPublished()) {
            return response()->json(['error' => 'Announcement not found or not published'], 404);
        }

        // Get client IP
        $clientIp = $request->ip();

        // Check if IP is allowed
        if (!$announcement->canBeAccessedByIp($clientIp)) {
            return response()->json([
                'error' => 'Access denied. Your IP address is not allowed to view this announcement.',
                'your_ip' => $clientIp,
                'allowed_ips' => $announcement->allowed_ips,
                'max_access_count' => $announcement->max_ip_access_count,
                'current_access_count' => $announcement->ip_access_log[$clientIp] ?? 0
            ], 403);
        }

        // Log the IP access
        $announcement->logIpAccess($clientIp);

        // Return announcement data
        return response()->json([
            'announcement' => $announcement->load(['vehicle', 'seller', 'currency', 'country', 'state', 'city']),
            'access_info' => [
                'your_ip' => $clientIp,
                'access_count' => $announcement->ip_access_log[$clientIp] ?? 1,
                'max_access_count' => $announcement->max_ip_access_count,
                'unique_ips' => $announcement->getUniqueIpAccessCount(),
                'total_accesses' => $announcement->getTotalIpAccessCount()
            ]
        ]);
    }

    /**
     * Check if an announcement can be accessed by current IP.
     */
    public function checkAccess(Request $request, $id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);
        $clientIp = $request->ip();

        return response()->json([
            'can_access' => $announcement->canBeAccessedByIp($clientIp),
            'your_ip' => $clientIp,
            'allowed_ips' => $announcement->allowed_ips,
            'max_access_count' => $announcement->max_ip_access_count,
            'current_access_count' => $announcement->ip_access_log[$clientIp] ?? 0,
            'is_published' => $announcement->isPublished(),
            'expires_at' => $announcement->expires_at,
            'is_expired' => $announcement->shouldBeExpired()
        ]);
    }

    /**
     * Get announcement statistics.
     */
    public function stats($id): JsonResponse
    {
        $announcement = Announcement::findOrFail($id);

        return response()->json([
            'unique_ips' => $announcement->getUniqueIpAccessCount(),
            'total_accesses' => $announcement->getTotalIpAccessCount(),
            'ip_access_log' => $announcement->ip_access_log,
            'views_count' => $announcement->views_count,
            'status' => $announcement->status,
            'published_at' => $announcement->published_at,
            'expires_at' => $announcement->expires_at,
            'is_expired' => $announcement->shouldBeExpired()
        ]);
    }
}
