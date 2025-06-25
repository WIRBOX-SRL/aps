<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'name',
        'subscription_type',
        'stripe_id',
        'stripe_status',
        'stripe_price',
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // Calculează data de expirare în funcție de tipul de abonament
    public function calculateExpiryDate()
    {
        $startDate = $this->created_at ?? now();

        switch ($this->subscription_type) {
            case 'daily':
                return $startDate->addDay();
            case 'monthly':
                return $startDate->addMonth();
            case 'yearly':
                return $startDate->addYear();
            default:
                return $startDate->addMonth();
        }
    }

    // Reînnoiește abonamentul
    public function renew()
    {
        $this->ends_at = $this->calculateExpiryDate();
        $this->stripe_status = 'active';
        $this->save();
    }

    // Upgrade la un plan nou
    public function upgradeToPlan($newPlanId)
    {
        $newPlan = \App\Models\Plan::find($newPlanId);
        if ($newPlan) {
            $this->plan_id = $newPlanId;
            $this->name = $newPlan->name;
            $this->save();
        }
    }

    // Anulează abonamentul
    public function cancel()
    {
        $this->stripe_status = 'canceled';
        $this->save();
    }

    public function getStatusAttribute()
    {
        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }
        return $this->stripe_status;
    }
}
