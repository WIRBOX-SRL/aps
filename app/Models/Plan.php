<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'user_limit',
        'resources',
    ];

    protected $casts = [
        'resources' => 'array',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions()
    {
        return $this->subscriptions()
            ->where(function ($query) {
                $query->where('ends_at', '>', now())
                      ->orWhereNull('ends_at');
            })
            ->where('stripe_status', '!=', 'canceled');
    }

    // Check if the plan allows a certain action on a resource
    public function canPerformAction($resource, $action)
    {
        if (!$this->resources) {
            return false;
        }

        // Check if it is a structure with string keys (Basic, Premium, Enterprise)
        if (isset($this->resources[$resource])) {
            $info = $this->resources[$resource];
            if (!isset($info['permissions'])) {
                return false;
            }
            return in_array($action, $info['permissions']);
        }

        // Check if it is a structure with numeric array (Premium Admin Plan)
        foreach ($this->resources as $resourceInfo) {
            if (isset($resourceInfo['resource']) && $resourceInfo['resource'] === $resource) {
                if (!isset($resourceInfo['permissions'])) {
                    return false;
                }
                return in_array($action, $resourceInfo['permissions']);
            }
        }

        return false;
    }

    // Get all available resources
    public function getAvailableResources()
    {
        if (!$this->resources) {
            return [];
        }

        $resources = [];

        // Check if it is a structure with string keys (Basic, Premium, Enterprise)
        if (is_array($this->resources) && !isset($this->resources[0])) {
            return array_keys($this->resources);
        }

        // Check if it is a structure with numeric array (Premium Admin Plan)
        foreach ($this->resources as $resourceInfo) {
            if (isset($resourceInfo['resource'])) {
                $resources[] = $resourceInfo['resource'];
            }
        }

        return $resources;
    }

    // Get all available actions for a resource
    public function getAvailableActions($resource)
    {
        if (!$this->resources) {
            return [];
        }

        // Check if it is a structure with string keys (Basic, Premium, Enterprise)
        if (isset($this->resources[$resource])) {
            return $this->resources[$resource]['permissions'] ?? [];
        }

        // Check if it is a structure with numeric array (Premium Admin Plan)
        foreach ($this->resources as $resourceInfo) {
            if (isset($resourceInfo['resource']) && $resourceInfo['resource'] === $resource) {
                return $resourceInfo['permissions'] ?? [];
            }
        }

        return [];
    }

    // Get the creation limit for a resource
    public function getCreateLimitForResource($resource)
    {
        if (!$this->resources) {
            return 0;
        }

        // Check if it is a structure with string keys (Basic, Premium, Enterprise)
        if (isset($this->resources[$resource])) {
            return $this->resources[$resource]['create_limit'] ?? 0;
        }

        // Check if it is a structure with numeric array (Premium Admin Plan)
        foreach ($this->resources as $resourceInfo) {
            if (isset($resourceInfo['resource']) && $resourceInfo['resource'] === $resource) {
                return $resourceInfo['create_limit'] ?? 0;
            }
        }

        return 0;
    }
}
