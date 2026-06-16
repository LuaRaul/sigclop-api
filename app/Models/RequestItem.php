<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_request_id',
        'product_id',
        'description',
        'quantity_requested',
        'quantity_approved',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
