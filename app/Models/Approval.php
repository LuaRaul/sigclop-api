<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    use HasFactory;

    protected $fillable = [
        'internal_request_id',
        'approved_by',
        'decision',
        'comment',
        'decided_at',
    ];

    protected $casts = ['decided_at' => 'datetime'];

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
