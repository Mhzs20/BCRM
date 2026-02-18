<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'salon_id',
        'name',
        'type',
        'description',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // انواع دسته‌بندی
    const TYPE_BOTH = 'both';       // برای هر دو ورودی و خروجی
    const TYPE_INCOME = 'income';   // فقط برای ورودی
    const TYPE_EXPENSE = 'expense'; // فقط برای خروجی

    /**
     * Relationships
     */
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

    public function subcategories()
    {
        return $this->hasMany(TransactionSubcategory::class, 'category_id');
    }

    public function activeSubcategories()
    {
        return $this->subcategories()->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scopes
     */
    public function scopeForSalon($query, $salonId)
    {
        return $query->where('salon_id', $salonId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where(function ($q) use ($type) {
            $q->where('type', $type)
              ->orWhere('type', self::TYPE_BOTH);
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Methods
     */
    public function isSystem(): bool
    {
        return $this->is_system === true;
    }

    public function canBeDeleted(): bool
    {
        // دسته‌های سیستمی قابل حذف نیستند
        return !$this->isSystem();
    }
}
