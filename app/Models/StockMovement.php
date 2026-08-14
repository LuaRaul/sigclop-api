<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $table = 'stock_movements';

    protected $fillable = [
        'product_id',
        'user_id',
        'distribution_id',
        'internal_request_id',
        'type',
        'quantity',
        'balance_before',
        'balance_after',
        'reference',
        'notes',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'user_id' => 'integer',
        'quantity' => 'integer',
        'balance_before' => 'integer',
        'balance_after' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Produto movimentado.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }

    /**
     * Utilizador que efectuou o movimento.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Entradas.
     */
    public function scopeEntradas($query)
    {
        return $query->where(
            'type',
            'entrada'
        );
    }

    /**
     * Saídas.
     */
    public function scopeSaidas($query)
    {
        return $query->where(
            'type',
            'saida'
        );
    }

    /**
     * Ajustes.
     */
    public function scopeAjustes($query)
    {
        return $query->where(
            'type',
            'ajuste'
        );
    }

    /**
     * Movimentos de um determinado produto.
     */
    public function scopeForProduct(
        $query,
        int $productId
    ) {
        return $query->where(
            'product_id',
            $productId
        );
    }

    /**
     * Movimentos realizados por determinado utilizador.
     */
    public function scopeByUser(
        $query,
        int $userId
    ) {
        return $query->where(
            'user_id',
            $userId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * É uma entrada?
     */
    public function isEntry(): bool
    {
        return $this->type === 'entrada';
    }

    /**
     * É uma saída?
     */
    public function isExit(): bool
    {
        return $this->type === 'saida';
    }

    /**
     * É um ajuste?
     */
    public function isAdjustment(): bool
    {
        return $this->type === 'ajuste';
    }

    /**
     * Nome amigável do tipo.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'entrada' => 'Entrada',
            'saida' => 'Saída',
            'ajuste' => 'Ajuste',
            'inventario' => 'Inventário',
            'distribuicao' => 'Distribuição',
            'devolucao' => 'Devolução',
            'reposicao' => 'Reposição',
            'transferencia' => 'Transferência',
            'perda' => 'Perda',
            default => ucfirst($this->type),
        };
    }

    /**
     * Sinal matemático do movimento.
     *
     * Entrada = +
     * Saída   = -
     * Ajuste  = alteração directa para o saldo indicado.
     */
    public function getMovementSignAttribute(): string
    {
        return match ($this->type) {
            'entrada' => '+',
            'saida' => '-',
            'ajuste' => '=',
            default => '',
        };
    }

    /**
     * Quantidade com sinal.
     *
     * Para ajustes não calculamos sinal porque quantity representa
     * o saldo pretendido.
     */
    public function getSignedQuantityAttribute(): int
    {
        return match ($this->type) {
            'entrada' => abs($this->quantity),
            'saida' => -abs($this->quantity),
            'ajuste' => $this->quantity,
            default => $this->quantity,
        };
    }

    /**
     * Texto descritivo.
     */
    public function getDescriptionAttribute(): string
    {
        return match ($this->type) {
            'entrada' =>
            "Entrada de {$this->quantity} unidade(s)",

            'saida' =>
            "Saída de {$this->quantity} unidade(s)",

            'ajuste' =>
            "Ajuste de stock para {$this->quantity} unidade(s)",

            default =>
            "Movimento de {$this->quantity} unidade(s)",
        };
    }

    /**
     * Saldo final formatado.
     */
    public function getBalanceAfterLabelAttribute(): string
    {
        return number_format(
            $this->balance_after,
            0,
            ',',
            '.'
        );
    }
    public function distribution(): BelongsTo
    {
        return $this->belongsTo(
            Distribution::class,
            'distribution_id'
        );
    }

    public function internalRequest(): BelongsTo
    {
        return $this->belongsTo(
            InternalRequest::class,
            'internal_request_id'
        );
    }
}
