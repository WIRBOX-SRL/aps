<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'root_id',
        'level',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    // Relația cu categoria părinte
    public function parent()
    {
        return $this->belongsTo(Category::class, 'root_id');
    }

    // Relația cu subcategoriile
    public function children()
    {
        return $this->hasMany(Category::class, 'root_id');
    }

    // Toate subcategoriile (recursive)
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    // Toate categoriile părinte (recursive)
    public function allParents()
    {
        return $this->parent()->with('allParents');
    }

    // Verifică dacă este o categorie principală (fără părinte)
    public function isMainCategory()
    {
        return is_null($this->root_id);
    }

    // Obține toate categoriile principale
    public static function getMainCategories()
    {
        return self::whereNull('root_id')->with('allChildren')->get();
    }

    // Construiește structura ierarhică pentru JSON
    public static function getHierarchicalStructure()
    {
        $mainCategories = self::getMainCategories();
        return $mainCategories->map(function ($category) {
            return self::buildCategoryTree($category);
        });
    }

    // Generează JSON-ul cu structura ierarhică
    public static function generateHierarchicalJson()
    {
        $structure = self::getHierarchicalStructure();
        return json_encode($structure, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // Construiește arborele pentru o categorie
    private static function buildCategoryTree($category)
    {
        $tree = [
            'id' => $category->id,
            'name' => $category->name,
            'level' => $category->level,
        ];

        if ($category->children->count() > 0) {
            $tree['children'] = $category->children->map(function ($child) {
                return self::buildCategoryTree($child);
            });
        }

        return $tree;
    }
}
