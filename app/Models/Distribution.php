<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_number',
        'department_id',
        'internal_request_id',
        'delivered_by',
        'status',
        'delivered_at',
        'received_by',
        'notes',
    ];

    protected $casts = ['delivered_at' => 'datetime'];

    public function items()
    {
        return $this->hasMany(DistributionItem::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function request()
    {
        return $this->belongsTo(InternalRequest::class, 'internal_request_id');
    }
}
