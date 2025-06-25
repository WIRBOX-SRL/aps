<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    // Returns the user's profile and cloud/domain settings
    public function show(Request $request)
    {
        $user = Auth::user();
        $cloudSettings = $user->getCloudSettings();
        $emailSettings = $user->getEmailSettings();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            // Email settings
            'smtp_host' => $emailSettings['smtp_host'],
            'smtp_username' => $emailSettings['smtp_username'],
            'smtp_password' => $emailSettings['smtp_password'],
            'smtp_encryption' => $emailSettings['smtp_encryption'],
            'smtp_port' => $emailSettings['smtp_port'],
            // Cloud/domain settings
            'cloudinary_cloud_name' => $cloudSettings['cloudinary_cloud_name'],
            'cloudinary_api_key' => $cloudSettings['cloudinary_api_key'],
            'cloudinary_api_secret' => $cloudSettings['cloudinary_api_secret'],
            'cloudflare_api_key' => $cloudSettings['cloudflare_api_key'],
            'cloudflare_zone_id' => $cloudSettings['cloudflare_zone_id'],
            'custom_domain' => $cloudSettings['custom_domain'],
        ]);
    }

    // Allows the admin to update their cloud/domain settings
    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole('Admin')) {
            return response()->json(['message' => 'Only admins can edit their settings.'], 403);
        }

        $data = $request->only([
            'cloudinary_cloud_name',
            'cloudinary_api_key',
            'cloudinary_api_secret',
            'cloudflare_api_key',
            'cloudflare_zone_id',
            'custom_domain',
        ]);

        $validator = Validator::make($data, [
            'cloudinary_cloud_name' => 'nullable|string|max:255',
            'cloudinary_api_key' => 'nullable|string|max:255',
            'cloudinary_api_secret' => 'nullable|string|max:255',
            'cloudflare_api_key' => 'nullable|string|max:255',
            'cloudflare_zone_id' => 'nullable|string|max:255',
            'custom_domain' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user->update($data);

        return response()->json(['message' => 'Settings have been successfully updated.']);
    }
}
