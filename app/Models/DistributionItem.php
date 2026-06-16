<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionItem extends Model
{
    use HasFactory;

    protected $fillable = ['distribution_id', 'product_id', 'quantity', 'notes'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
