<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestItem extends Model
{
    use HasFactory;

    protected $table = 'request_items';

    protected $fillable = [
        'internal_request_id',
        'product_id',
        'description',
        'quantity_requested',
        'quantity_approved',
        'notes',
    ];

    protected $casts = [
        'internal_request_id' => 'integer',
        'product_id' => 'integer',
        'quantity_requested' => 'integer',
        'quantity_approved' => 'integer',
    ];

    protected $attributes = [
        'quantity_approved' => 0,
    ];

    protected $appends = [
        'product_name',
        'sku',
        'unit',
        'category_name',
        'request_number',
        'approval_status',
        'pending_approval_quantity',
        'summary',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Pedido interno.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(
            InternalRequest::class,
            'internal_request_id'
        );
    }

    /**
     * Produto.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeApproved($query)
    {
        return $query->where(
            'quantity_approved',
            '>',
            0
        );
    }

    public function scopePending($query)
    {
        return $query->whereColumn(
            'quantity_approved',
            '<',
            'quantity_requested'
        );
    }

    public function scopeFullyApproved($query)
    {
        return $query->whereColumn(
            'quantity_approved',
            '>=',
            'quantity_requested'
        );
    }

    public function scopePartiallyApproved($query)
    {
        return $query
            ->where(
                'quantity_approved',
                '>',
                0
            )
            ->whereColumn(
                'quantity_approved',
                '<',
                'quantity_requested'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Foi aprovado?
     */
    public function isApproved(): bool
    {
        return $this->quantity_approved > 0;
    }

    /**
     * Aprovação total.
     */
    public function isFullyApproved(): bool
    {
        return $this->quantity_approved >=
            $this->quantity_requested;
    }

    /**
     * Aprovação parcial.
     */
    public function isPartiallyApproved(): bool
    {
        return $this->quantity_approved > 0
            && $this->quantity_approved <
                $this->quantity_requested;
    }

    /**
     * Quantidade ainda não aprovada.
     */
    public function pendingQuantity(): int
    {
        return max(
            0,
            $this->quantity_requested
            - $this->quantity_approved
        );
    }

    /**
     * Quantidade aprovada válida.
     */
    public function approvedQuantity(): int
    {
        return min(
            $this->quantity_requested,
            max(
                0,
                $this->quantity_approved
            )
        );
    }

    /**
     * Define a quantidade aprovada.
     */
    public function approveQuantity(
        int $quantity
    ): void {
        $quantity = max(
            0,
            min(
                $quantity,
                $this->quantity_requested
            )
        );

        $this->quantity_approved = $quantity;
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

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

    public function getRequestNumberAttribute(): ?string
    {
        return $this->request?->request_number;
    }

    public function getApprovalStatusAttribute(): string
    {
        if ($this->isFullyApproved()) {
            return 'Totalmente Aprovado';
        }

        if ($this->isPartiallyApproved()) {
            return 'Parcialmente Aprovado';
        }

        return 'Pendente';
    }

    public function getApprovalStatusCodeAttribute(): string
    {
        if ($this->isFullyApproved()) {
            return 'approved';
        }

        if ($this->isPartiallyApproved()) {
            return 'partial';
        }

        return 'pending';
    }

    public function getPendingApprovalQuantityAttribute(): int
    {
        return $this->pendingQuantity();
    }

    public function getSummaryAttribute(): string
    {
        $name = $this->product_name
            ?? $this->description
            ?? 'Produto';

        return sprintf(
            '%s | %d/%d',
            $name,
            $this->quantity_approved,
            $this->quantity_requested
        );
    }
}
