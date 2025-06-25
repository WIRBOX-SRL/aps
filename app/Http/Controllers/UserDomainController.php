<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Announcement;
use App\Models\Seller;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class UserDomainController extends Controller
{
    // Returnează datele adminului și resursele asociate pe baza domeniului custom
    public function show($domain)
    {
        // Caută adminul cu domeniul respectiv
        $admin = User::where('custom_fields->custom_domain', $domain)
            ->whereHas('roles', function ($q) { $q->where('name', 'Admin'); })
            ->first();

        if (!$admin) {
            return response()->json(['message' => 'Admin not found for this domain.'], 404);
        }

        // Setări cloud/email
        $cloudSettings = $admin->getCloudSettings();
        $emailSettings = $admin->getEmailSettings();

        // Plan și subscripție
        $subscription = $admin->getActiveSubscription();
        $plan = $admin->getActivePlan();

        // Userii asociați acestui admin
        $userIds = $admin->createdUsers()->pluck('id')->toArray();
        $userIds[] = $admin->id;
        $users = User::whereIn('id', $userIds)->with('roles')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ];
        });

        // Anunțuri (ale adminului și userilor săi)
        $announcements = Announcement::whereIn('user_id', $userIds)->get();

        // Sellers (ai adminului și userilor săi)
        $sellers = Seller::whereIn('user_id', $userIds)->get();

        // Vehicles (ale adminului și userilor săi)
        $vehicles = Vehicle::whereIn('user_id', $userIds)->get();

        return response()->json([
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'avatar_url' => $admin->avatar_url ?? null,
                'role' => $admin->getRoleNames()->first(),
                'cloud_settings' => $cloudSettings,
                'email_settings' => $emailSettings,
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'plan_id' => $subscription->plan_id,
                    'name' => $subscription->name,
                    'ends_at' => $subscription->ends_at,
                    'stripe_status' => $subscription->stripe_status,
                ] : null,
                'plan' => $plan ? [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'user_limit' => $plan->user_limit,
                    'resources' => $plan->resources,
                ] : null,
            ],
            'users' => $users,
            'announcements' => $announcements,
            'sellers' => $sellers,
            'vehicles' => $vehicles,
        ]);
    }
}
