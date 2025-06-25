<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'pin',
        'title',
        'content',
        'price',
        'vat',
        'currency_id',
        'user_id',
        'seller_id',
        'vehicle_id',
        'country_id',
        'state_id',
        'city_id',
        'link',
        'type',
        'status',
        'published_at',
        'expires_at',
        'target_roles',
        'allowed_ips',
        'max_ip_access_count',
        'ip_access_log',
        'is_pinned',
        'views_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'target_roles' => 'array',
        'allowed_ips' => 'array',
        'ip_access_log' => 'array',
        'is_pinned' => 'boolean',
        'views_count' => 'integer',
        'price' => 'decimal:2',
        'vat' => 'decimal:2',
    ];

    protected $attributes = [
        'max_ip_access_count' => 0,
        'allowed_ips' => '[]',
        'ip_access_log' => '[]',
    ];

    // Mutators for IP fields
    public function setAllowedIpsAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['allowed_ips'] = '[]';
        } elseif (is_array($value)) {
            // Handle both formats: simple array and repeater format
            if (isset($value[0]) && is_array($value[0]) && isset($value[0]['ip'])) {
                // Repeater format: [{'ip': '192.168.1.1'}, {'ip': '10.0.0.1'}]
                $ips = collect($value)->pluck('ip')->filter()->toArray();
            } else {
                // Simple array format: ['192.168.1.1', '10.0.0.1']
                $ips = array_filter($value);
            }
            $this->attributes['allowed_ips'] = json_encode($ips);
        } else {
            $this->attributes['allowed_ips'] = $value;
        }
    }

    public function setMaxIpAccessCountAttribute($value)
    {
        $this->attributes['max_ip_access_count'] = $value ?? 0;
    }

    public function setIpAccessLogAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['ip_access_log'] = '[]';
        } elseif (is_array($value)) {
            $this->attributes['ip_access_log'] = json_encode($value);
        } else {
            $this->attributes['ip_access_log'] = $value;
        }
    }

    // Relationship with user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship with seller
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    // Relationship with vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // Relationship with currency
    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    // Relationship with country
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    // Relationship with state
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    // Relationship with city
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // Scope for published announcements
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    });
    }

    // Scope for active announcements (published and not expired)
    public function scopeActive($query)
    {
        return $query->published();
    }

    // Scope for pinned announcements
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    // Scope for announcements by type
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Check if the announcement is published
    public function isPublished()
    {
        return $this->status === 'published' &&
               $this->published_at &&
               $this->published_at <= now() &&
               (!$this->expires_at || $this->expires_at > now());
    }

    // Check if the announcement is expired
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at <= now();
    }

    // Check if the announcement is pinned
    public function isPinned()
    {
        return $this->is_pinned;
    }

    // Increment the number of views
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    // Publish the announcement
    public function publish()
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    // Archive the announcement
    public function archive()
    {
        $this->update(['status' => 'archived']);
    }

    // Check if the user can view the announcement
    public function canBeViewedBy($user)
    {
        if (!$this->isPublished()) {
            return false;
        }

        // If no target roles are specified, everyone can view
        if (empty($this->target_roles)) {
            return true;
        }

        // Check if the user has one of the target roles
        return $user->hasAnyRole($this->target_roles);
    }

    // Calculate the price with VAT
    public function getPriceWithVatAttribute()
    {
        if (!$this->price) {
            return 0;
        }
        return $this->price * (1 + ($this->vat / 100));
    }

    // Calculate the VAT value
    public function getVatAmountAttribute()
    {
        if (!$this->price) {
            return 0;
        }
        return $this->price * ($this->vat / 100);
    }

    // Check if IP is allowed to access this announcement
    public function isIpAllowed($ip)
    {
        // If no IP restrictions, allow all
        if (empty($this->allowed_ips)) {
            return true;
        }

        return in_array($ip, $this->allowed_ips);
    }

    // Check if IP access limit is reached
    public function isIpAccessLimitReached($ip)
    {
        // If no limit set, always allow
        if ($this->max_ip_access_count <= 0) {
            return false;
        }

        $ipLog = $this->ip_access_log ?? [];
        $ipAccessCount = $ipLog[$ip] ?? 0;

        return $ipAccessCount >= $this->max_ip_access_count;
    }

    // Log IP access
    public function logIpAccess($ip)
    {
        $ipLog = $this->ip_access_log ?? [];
        $ipLog[$ip] = ($ipLog[$ip] ?? 0) + 1;

        $this->update(['ip_access_log' => $ipLog]);
    }

    // Check if announcement can be accessed by IP
    public function canBeAccessedByIp($ip)
    {
        return $this->isIpAllowed($ip) && !$this->isIpAccessLimitReached($ip);
    }

    // Get unique IP access count
    public function getUniqueIpAccessCount()
    {
        $ipLog = $this->ip_access_log ?? [];
        return count($ipLog);
    }

    // Get total IP access count
    public function getTotalIpAccessCount()
    {
        $ipLog = $this->ip_access_log ?? [];
        return array_sum($ipLog);
    }

    // Check if announcement should be expired
    public function shouldBeExpired()
    {
        return $this->expires_at && $this->expires_at <= now();
    }

    // Expire the announcement (set to draft)
    public function expire()
    {
        $this->update([
            'status' => 'draft',
        ]);
    }

    // Scope for non-expired announcements
    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    // Scope for announcements accessible by IP
    public function scopeAccessibleByIp($query, $ip)
    {
        return $query->where(function ($q) use ($ip) {
            $q->whereNull('allowed_ips')
              ->orWhereJsonContains('allowed_ips', $ip);
        });
    }

    // Generate a unique 8-character PIN
    public static function generateUniquePin(): string
    {
        do {
            $pin = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8));
        } while (static::where('pin', $pin)->exists());

        return $pin;
    }

    // Validate PIN
    public function validatePin(string $pin): bool
    {
        return $this->pin === strtoupper($pin);
    }

    // Boot method to auto-generate PIN on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($announcement) {
            if (empty($announcement->pin)) {
                $announcement->pin = static::generateUniquePin();
            }
        });
    }
}
