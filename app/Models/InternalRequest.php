<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'department_id',
        'requested_by',
        'assigned_approver_id',
        'priority',
        'status',
        'justification',
        'needed_at',
        'submitted_at',
    ];

    protected $casts = [
        'needed_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(RequestItem::class);
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'assigned_approver_id');
    }
}
