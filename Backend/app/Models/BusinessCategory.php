<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'is_main_selectable'];

    public function subcategories()
    {
        return $this->hasMany(BusinessSubcategory::class, 'category_id');
    }
}