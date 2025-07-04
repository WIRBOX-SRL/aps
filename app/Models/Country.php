<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['name', 'code'];

    public function cities()
    {
        return $this->hasMany(City::class);
    }

    public function states()
    {
        return $this->hasMany(State::class);
    }
}
