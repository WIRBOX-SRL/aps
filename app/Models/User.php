<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;



class User extends Authenticatable implements HasAvatar
{
    use HasFactory, Notifiable, HasRoles; // ✅ replaced with correct trait

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'custom_fields',
        'created_by',
        'is_suspended',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'subscription_status',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'avatar_url' => 'string',
            'custom_fields' => 'array',
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatarColumn = config('filament-edit-profile.avatar_column', 'avatar_url');
        return $this->$avatarColumn ? Storage::url($this->$avatarColumn) : null;
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->where('ends_at', '>', now())->orWhereNull('ends_at');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Get the user's active subscription
    public function getActiveSubscription()
    {
        return $this->subscriptions()
            ->where(function ($query) {
                $query->where('ends_at', '>', now())
                      ->orWhereNull('ends_at');
            })
            ->where('stripe_status', '!=', 'canceled')
            ->first();
    }

    // Check if the user has an active subscription
    public function hasActiveSubscription()
    {
        return $this->getActiveSubscription() !== null;
    }

    // Get the user's active plan
    public function getActivePlan()
    {
        $subscription = $this->getActiveSubscription();
        return $subscription ? $subscription->plan : null;
    }

    // Check if the user can create more users
    public function canCreateMoreUsers()
    {
        // Super Admin has no limitations
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $plan = $this->getActivePlan();
        if (!$plan) {
            return false;
        }

        $currentUserCount = $this->createdUsers()->count();
        return $currentUserCount < $plan->user_limit;
    }

    // Check if the user can create more posts
    public function canCreateMorePosts()
    {
        // Super Admin has no limitations
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $plan = $this->getActivePlan();
        if (!$plan) {
            return false;
        }

        // Here you can add logic for the number of posts
        // For now, return true if they have an active subscription
        return true;
    }

    // Check if the user can access a resource with a certain action
    public function canAccessResource($resource, $action)
    {
        // Super Admin has access to everything
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $plan = $this->getActivePlan();
        if (!$plan) {
            return false;
        }

        return $plan->canPerformAction($resource, $action);
    }

    public function canImpersonate()
    {
        return true;
    }

    // Check if the user can create more entities of a resource
    public function canCreateMoreOfResource($resource, $currentCount)
    {
        // Super Admin has no limitations
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        if (!$this->hasActiveSubscription()) {
            return false;
        }

        $plan = $this->getActivePlan();
        if (!$plan) {
            return false;
        }

        $limit = $plan->getCreateLimitForResource($resource);
        if ($limit === 0) {
            return true; // 0 = unlimited
        }
        return $currentCount < $limit;
    }

    // Suspend the user and all their users (cascade)
    public function suspendWithCascade()
    {
        $this->is_suspended = true;
        $this->save();

        // Suspend all users created by this user
        $this->createdUsers()->update(['is_suspended' => true]);

        // If Admin, also suspend users created by their users (recursive)
        if ($this->hasRole('Admin')) {
            $this->suspendAllNestedUsers();
        }
    }

    // Recursively suspend all nested users
    private function suspendAllNestedUsers()
    {
        $directUsers = $this->createdUsers;
        foreach ($directUsers as $user) {
            $user->suspendWithCascade();
        }
    }

    // Unsuspend the user and all their users (cascade)
    public function unsuspendWithCascade()
    {
        $this->is_suspended = false;
        $this->save();

        // Unsuspend all users created by this user
        $this->createdUsers()->update(['is_suspended' => false]);

        // If Admin, unsuspend users created by their users (recursive)
        if ($this->hasRole('Admin')) {
            $this->unsuspendAllNestedUsers();
        }
    }

    // Recursively unsuspend all nested users
    private function unsuspendAllNestedUsers()
    {
        $directUsers = $this->createdUsers;
        foreach ($directUsers as $user) {
            $user->unsuspendWithCascade();
        }
    }

    // Check if admin responsible has an active subscription
    public function adminHasActiveSubscription()
    {
        // Super Admin has no limitations
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        // If no creator, no access
        if (!$this->created_by) {
            return false;
        }

        $admin = User::find($this->created_by);
        if (!$admin) {
            return false;
        }

        return $admin->hasActiveSubscription();
    }

    // Check if the user can access resources based on admin subscription
    public function canAccessBasedOnAdminSubscription()
    {
        // Super Admin has access to everything
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        // Admin checks their own subscription
        if ($this->hasRole('Admin')) {
            return $this->hasActiveSubscription();
        }

        // User checks admin's subscription
        if ($this->hasRole('User')) {
            return $this->adminHasActiveSubscription();
        }

        return false;
    }

    // Check if the user can create more users based on admin subscription
    public function canCreateMoreUsersBasedOnAdminSubscription()
    {
        // Super Admin has no limitations
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        // Admin checks their own subscription
        if ($this->hasRole('Admin')) {
            return $this->canCreateMoreUsers();
        }

        // User cannot create users
        return false;
    }

    // Check if the user can create more entities of a resource based on admin subscription
    public function canCreateMoreOfResourceBasedOnAdminSubscription($resource, $currentCount)
    {
        // Super Admin has no limitations
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        // Admin checks their own subscription
        if ($this->hasRole('Admin')) {
            return $this->canCreateMoreOfResource($resource, $currentCount);
        }

        // User checks admin's subscription
        if ($this->hasRole('User')) {
            if (!$this->adminHasActiveSubscription()) {
                return false;
            }

            // Get admin's plan
            $admin = User::find($this->created_by);
            if (!$admin) {
                return false;
            }

            $plan = $admin->getActivePlan();
            if (!$plan) {
                return false;
            }

            $limit = $plan->getCreateLimitForResource($resource);
            if ($limit === 0) {
                return true; // 0 = unlimited
            }
            return $currentCount < $limit;
        }

        return false;
    }

    // Check if the user can access a resource with a certain action based on admin subscription
    public function canAccessResourceBasedOnAdminSubscription($resource, $action)
    {
        // Super Admin has access to everything
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        // Admin checks their own subscription
        if ($this->hasRole('Admin')) {
            return $this->canAccessResource($resource, $action);
        }

        // User checks admin's subscription
        if ($this->hasRole('User')) {
            if (!$this->adminHasActiveSubscription()) {
                return false;
            }

            // Get admin's plan
            $admin = User::find($this->created_by);
            if (!$admin) {
                return false;
            }

            $plan = $admin->getActivePlan();
            if (!$plan) {
                return false;
            }

            return $plan->canPerformAction($resource, $action);
        }

        return false;
    }

    // Return cloud/domain settings for user or admin
    public function getCloudSettings()
    {
        // If regular user, get from admin
        if ($this->hasRole('User') && $this->creator) {
            return $this->creator->getCloudSettings();
        }
        // For admin, return own settings
        $fields = $this->custom_fields ?? [];
        return [
            'cloudinary_cloud_name' => $fields['cloudinary_cloud_name'] ?? null,
            'cloudinary_api_key' => $fields['cloudinary_api_key'] ?? null,
            'cloudinary_api_secret' => $fields['cloudinary_api_secret'] ?? null,
            'cloudflare_api_key' => $fields['cloudflare_api_key'] ?? null,
            'cloudflare_zone_id' => $fields['cloudflare_zone_id'] ?? null,
            'custom_domain' => $fields['custom_domain'] ?? null,
        ];
    }

    // Check if admin has Cloudinary configured completely
    public function hasCloudinaryConfigured()
    {
        $settings = $this->getCloudSettings();
        return !empty($settings['cloudinary_cloud_name']) && !empty($settings['cloudinary_api_key']) && !empty($settings['cloudinary_api_secret']);
    }

    // Return admin associated for upload (self if admin, creator if user)
    public function getAdminForUpload()
    {
        if ($this->hasRole('Admin')) {
            return $this;
        }
        if ($this->hasRole('User') && $this->creator) {
            return $this->creator;
        }
        return $this;
    }

    // Return email settings for user or admin
    public function getEmailSettings()
    {
        $fields = $this->custom_fields ?? [];
        $hasAny = isset($fields['smtp_host']) || isset($fields['smtp_username']) || isset($fields['smtp_password']) || isset($fields['smtp_encryption']) || isset($fields['smtp_port']);
        if (!$hasAny && $this->hasRole('User') && $this->creator) {
            return $this->creator->getEmailSettings();
        }
        return [
            'smtp_host' => $fields['smtp_host'] ?? null,
            'smtp_username' => $fields['smtp_username'] ?? null,
            'smtp_password' => $fields['smtp_password'] ?? null,
            'smtp_encryption' => $fields['smtp_encryption'] ?? null,
            'smtp_port' => $fields['smtp_port'] ?? null,
        ];
    }

    public function getSubscriptionStatusAttribute()
    {
        if ($this->hasRole('Super Admin')) {
            return 'Full access';
        }
        if ($this->hasRole('Admin')) {
            $subscription = $this->getActiveSubscription();
            if ($subscription) {
                $expires = $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i') : 'unlimited';
                return 'Expired at: ' . $expires;
            }
            return 'No subscription';
        }
        if ($this->hasRole('User')) {
            $admin = $this->creator;
            if ($admin && $admin->hasActiveSubscription()) {
                $subscription = $admin->getActiveSubscription();
                $expires = $subscription && $subscription->ends_at ? $subscription->ends_at->format('Y-m-d H:i') : 'unlimited';
                return 'Expired at: ' . $expires;
            }
            return 'No subscription';
        }
        return 'No subscription';
    }
}
