<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'images',
        'category_id',
        'user_id',
        'brand',
        'model',
        'year',
        'condition',
        'price',
        'vat',
        'currency',
        'location',
        'contact_phone',
        'contact_email',
        'specifications',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'price' => 'decimal:2',
        'vat' => 'decimal:2',
        'specifications' => 'array',
        'images' => 'array',
    ];

    // Relația cu categoria
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relația cu utilizatorul
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scope pentru vehicule active
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Scope pentru vehicule în funcție de categorie
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Scope pentru căutare
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('brand', 'like', "%{$search}%")
              ->orWhere('model', 'like', "%{$search}%");
        });
    }

    // Verifică dacă vehiculul este activ
    public function isActive()
    {
        return $this->status === 'active';
    }

    // Verifică dacă vehiculul este vândut
    public function isSold()
    {
        return $this->status === 'sold';
    }

    // Formatează prețul cu moneda
    public function getFormattedPriceAttribute()
    {
        if (!$this->price) {
            return 'Price on request';
        }

        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    // Obține prima imagine
    public function getFirstImageAttribute()
    {
        if ($this->images && count($this->images) > 0) {
            return $this->images[0];
        }
        return null;
    }

    // Calculează prețul cu TVA
    public function getPriceWithVatAttribute()
    {
        if (!$this->price) {
            return 0;
        }
        return $this->price * (1 + ($this->vat / 100));
    }

    // Calculează valoarea TVA
    public function getVatAmountAttribute()
    {
        if (!$this->price) {
            return 0;
        }
        return $this->price * ($this->vat / 100);
    }

    // Obține prima fotografie (alias pentru compatibilitate)
    public function getFirstPhotoAttribute()
    {
        return $this->first_image;
    }

    // Obține toate fotografiile (alias pentru compatibilitate)
    public function getAllPhotosAttribute()
    {
        return $this->images ?? [];
    }
}
