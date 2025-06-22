<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;



class User extends Authenticatable implements HasAvatar
{
    use HasFactory, Notifiable; // ✅ înlocuit cu trait corect

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'custom_fields',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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

    public function planSubscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscriber');
    }

    public function activePlan(): ?\Laravelcm\Subscriptions\Models\Plan
    {
        return $this->planSubscriptions()->active()->first()?->plan;
    }

    public function canImpersonate()
    {
        return true;
    }
}
