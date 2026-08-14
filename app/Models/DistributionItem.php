<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DistributionItem extends Model
{
    use HasFactory;

    protected $table = 'distribution_items';

    protected $fillable = [
        'distribution_id',
        'product_id',
        'request_item_id',
        'quantity_requested',
        'quantity_distributed',
        'quantity_received',
        'quantity_returned',
        'status',
        'notes',
        'delivered_at',
        'received_at',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_distributed' => 'integer',
        'quantity_received' => 'integer',
        'quantity_returned' => 'integer',
        'delivered_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    protected $appends = [
        'product_name',
        'sku',
        'unit',
        'category_name',
        'distribution_number',
        'summary',
    ];

    public function distribution(): BelongsTo
    {
        return $this->belongsTo(
            Distribution::class,
            'distribution_id'
        );
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    public function requestItem(): BelongsTo
    {
        return $this->belongsTo(
            RequestItem::class,
            'request_item_id'
        );
    }

    public function hasQuantity(): bool
    {
        return $this->quantity_distributed > 0;
    }

    public function isPending(): bool
    {
        return $this->status === 'pendente';
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
        return $this->status === 'recebido';
    }

    public function isReturned(): bool
    {
        return $this->status === 'devolvido';
    }

    public function getPendingQuantityAttribute(): int
    {
        return max(
            0,
            $this->quantity_distributed
            - $this->quantity_received
        );
    }

    public function getProductNameAttribute(): ?string
    {
        return $this->product?->name;
    }

    public function getSkuAttribute(): ?string
    {
        return $this->product?->sku;
    }

    public function getUnitAttribute(): ?string
    {
        return $this->product?->unit;
    }

    public function getCategoryNameAttribute(): ?string
    {
        return $this->product?->category?->name;
    }

    public function getDistributionNumberAttribute(): ?string
    {
        return $this->distribution?->distribution_number;
    }

    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s - %d %s',
            $this->product_name ?? 'Produto',
            $this->quantity_distributed,
            $this->unit ?? ''
        );
    }
}
