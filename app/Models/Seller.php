<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'is_company',
        'language',
        'member_since',
        'member_since_human',
        'company',
        'address',
        'logo',
        'website',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'logo' => 'array', // Assuming logo is a string URL or path
            'is_company' => 'boolean', // Assuming is_company is a boolean
            'avatar' => 'array', // Assuming avatar is a string URL or path
            // Remove language from casts since we'll handle it manually
        ];
    }

    // Accessor for language field - convert string to array
    public function getLanguageAttribute($value)
    {
        if (is_null($value)) {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        // Try to decode JSON first
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If not JSON, split by comma
        return array_filter(array_map('trim', explode(',', $value)));
    }

    // Mutator for language field - convert array to string
    public function setLanguageAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['language'] = null;
            return;
        }

        if (is_string($value)) {
            $this->attributes['language'] = $value;
            return;
        }

        if (is_array($value)) {
            // Filter out empty values and trim
            $filtered = array_filter(array_map('trim', $value));
            $this->attributes['language'] = json_encode($filtered);
            return;
        }

        $this->attributes['language'] = null;
    }

    /**
     * Get the products associated with the seller.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
