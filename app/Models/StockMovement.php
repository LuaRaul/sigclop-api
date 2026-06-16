<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'balance_after',
        'reference',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
