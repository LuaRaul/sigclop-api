<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Distribution extends Model
{
    use HasFactory;

    protected $table = 'distributions';

    protected $fillable = [
        'distribution_number',
        'internal_request_id',
        'department_id',
        'delivered_by',
        'received_by',
        'confirmed_by',
        'status',
        'vehicle',
        'driver',
        'receipt_number',
        'prepared_at',
        'dispatched_at',
        'delivered_at',
        'received_at',
        'confirmed_at',
        'confirmed',
        'notes',
    ];

    protected $casts = [
        'prepared_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'confirmed' => 'boolean',
    ];

    protected $appends = [
        'items_count',
        'total_quantity',
        'department_name',
        'delivered_by_name',
        'received_by_name',
        'confirmed_by_name',
        'status_label',
        'display_name',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(
            DistributionItem::class,
            'distribution_id'
        );
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(
            InternalRequest::class,
            'internal_request_id'
        );
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'delivered_by'
        );
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'received_by'
        );
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'confirmed_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePreparing($query)
    {
        return $query->where(
            'status',
            'em_preparacao'
        );
    }

    public function scopeApproved($query)
    {
        return $query->where(
            'status',
            'aprovada'
        );
    }

    public function scopeInTransit($query)
    {
        return $query->where(
            'status',
            'em_transporte'
        );
    }

    public function scopeDelivered($query)
    {
        return $query->where(
            'status',
            'entregue'
        );
    }

    public function scopeReceived($query)
    {
        return $query->where(
            'status',
            'recebida'
        );
    }

    public function scopeCancelled($query)
    {
        return $query->where(
            'status',
            'cancelada'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADO
    |--------------------------------------------------------------------------
    */

    public function isPreparing(): bool
    {
        return $this->status === 'em_preparacao';
    }

    public function isApproved(): bool
    {
        return $this->status === 'aprovada';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'em_transporte';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'entregue';
    }

    public function isReceived(): bool
    {
        return $this->status === 'recebida';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelada';
    }

    public function isFinalized(): bool
    {
        return in_array(
            $this->status,
            [
                'recebida',
                'cancelada',
            ],
            true
        );
    }

    public function canBeDispatched(): bool
    {
        return in_array(
            $this->status,
            [
                'em_preparacao',
                'aprovada',
            ],
            true
        );
    }

    public function canBeDelivered(): bool
    {
        return in_array(
            $this->status,
            [
                'em_transporte',
                'aprovada',
            ],
            true
        );
    }

    public function canBeReceived(): bool
    {
        return $this->status === 'entregue';
    }

    public function canBeConfirmed(): bool
    {
        return $this->status === 'recebida'
            && !$this->confirmed;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getItemsCountAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return $this->items->count();
        }

        return $this->items()->count();
    }

    public function getTotalQuantityAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum(
                'quantity_distributed'
            );
        }

        return (int) $this->items()->sum(
            'quantity_distributed'
        );
    }

    public function getDepartmentNameAttribute(): ?string
    {
        return $this->department?->name;
    }

    public function getDeliveredByNameAttribute(): ?string
    {
        return $this->deliveredBy?->name;
    }

    public function getReceivedByNameAttribute(): ?string
    {
        return $this->receivedBy?->name;
    }

    public function getConfirmedByNameAttribute(): ?string
    {
        return $this->confirmedBy?->name;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'em_preparacao' => 'Em preparação',
            'aprovada' => 'Aprovada',
            'em_transporte' => 'Em transporte',
            'entregue' => 'Entregue',
            'recebida' => 'Recebida',
            'cancelada' => 'Cancelada',
            default => ucfirst(
                str_replace(
                    '_',
                    ' ',
                    $this->status
                )
            ),
        };
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->distribution_number;
    }
}
