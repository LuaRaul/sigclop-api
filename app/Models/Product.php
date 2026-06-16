<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'supplier_id',
        'name',
        'sku',
        'unit',
        'quantity',
        'minimum_quantity',
        'estimated_unit_cost',
        'active',
        'description',
    ];

    protected $casts = [
        'active' => 'boolean',
        'estimated_unit_cost' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_quantity;
    }
}
