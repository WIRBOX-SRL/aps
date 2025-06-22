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
            'logo' => 'string', // Assuming logo is a string URL or path
            'is_company' => 'boolean', // Assuming is_company is a boolean
            'avatar' => 'string', // Assuming avatar is a string URL or path
            'language' => 'array', // Assuming language is an array of strings
        ];
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
