<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory;

    /**
     * Tabela.
     */
    protected $table = 'products';

    /**
     * Campos preenchíveis.
     *
     * IMPORTANTE:
     * quantity pertence ao histórico de movimentos de stock.
     * O campo continua aqui para permitir a criação inicial,
     * mas alterações posteriores devem passar pelo
     * StockMovementController.
     */
    protected $fillable = [
        'product_category_id',
        'supplier_id',
        'name',
        'sku',
        'barcode',
        'unit',
        'quantity',
        'minimum_quantity',
        'maximum_quantity',
        'estimated_unit_cost',
        'location',
        'active',
        'description',
        'last_inventory_at',
        'last_inventory_by',
    ];

    /**
     * Conversões.
     */
    protected $casts = [
        'active' => 'boolean',

        'quantity' => 'integer',

        'minimum_quantity' => 'integer',

        'maximum_quantity' => 'integer',

        'estimated_unit_cost' => 'decimal:2',

        'last_inventory_at' => 'datetime',

        'last_inventory_by' => 'integer',
    ];

    /**
     * Valores padrão.
     */
    protected $attributes = [
        'active' => true,

        'quantity' => 0,

        'minimum_quantity' => 0,

        'estimated_unit_cost' => 0,
    ];

    /**
     * Atributos adicionais disponíveis no JSON.
     */
    protected $appends = [
        'category_name',
        'supplier_name',
        'stock_status',
        'stock_value',
        'display_name',
        'stock_percentage',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Categoria do produto.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            ProductCategory::class,
            'product_category_id'
        );
    }

    /**
     * Fornecedor.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }

    /**
     * Utilizador que realizou o último inventário.
     */
    public function lastInventoryBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'last_inventory_by'
        );
    }

    /**
     * Itens de pedidos internos.
     */
    public function requestItems(): HasMany
    {
        return $this->hasMany(
            RequestItem::class,
            'product_id'
        );
    }

    /**
     * Movimentos de stock.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(
            StockMovement::class,
            'product_id'
        );
    }

    /**
     * Último movimento de stock.
     */
    public function latestMovement(): HasOne
    {
        return $this->hasOne(
            StockMovement::class,
            'product_id'
        )->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Apenas produtos activos.
     */
    public function scopeActive($query)
    {
        return $query->where(
            'active',
            true
        );
    }

    /**
     * Apenas produtos inactivos.
     */
    public function scopeInactive($query)
    {
        return $query->where(
            'active',
            false
        );
    }

    /**
     * Produtos com stock.
     */
    public function scopeAvailable($query)
    {
        return $query->where(
            'quantity',
            '>',
            0
        );
    }

    /**
     * Produtos sem stock.
     */
    public function scopeOutOfStock($query)
    {
        return $query->where(
            'quantity',
            '<=',
            0
        );
    }

    /**
     * Stock abaixo do mínimo.
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn(
            'quantity',
            '<=',
            'minimum_quantity'
        );
    }

    /**
     * Stock acima do máximo.
     */
    public function scopeOverStock($query)
    {
        return $query
            ->whereNotNull('maximum_quantity')
            ->whereColumn(
                'quantity',
                '>',
                'maximum_quantity'
            );
    }

    /**
     * Pesquisa por nome, SKU ou código de barras.
     */
    public function scopeSearch(
        $query,
        string $search
    ) {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->where(function ($q) use ($search) {

            $q->where(
                'name',
                'like',
                "%{$search}%"
            )
                ->orWhere(
                    'sku',
                    'like',
                    "%{$search}%"
                )
                ->orWhere(
                    'barcode',
                    'like',
                    "%{$search}%"
                );
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS — STOCK
    |--------------------------------------------------------------------------
    */

    /**
     * Existe stock?
     */
    public function hasStock(): bool
    {
        return $this->quantity > 0;
    }

    /**
     * Está sem stock?
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    /**
     * Está com stock baixo?
     */
    public function isLowStock(): bool
    {
        return $this->quantity <= $this->minimum_quantity;
    }

    /**
     * Está acima do stock máximo?
     */
    public function isOverStock(): bool
    {
        return $this->maximum_quantity !== null
            && $this->quantity > $this->maximum_quantity;
    }

    /**
     * Pode fornecer determinada quantidade?
     */
    public function canSupply(
        int $quantity
    ): bool {
        if ($quantity < 0) {
            return false;
        }

        return $this->quantity >= $quantity;
    }

    /**
     * Quantidade disponível depois de uma saída.
     */
    public function remainingAfter(
        int $quantity
    ): int {
        return max(
            0,
            $this->quantity - $quantity
        );
    }

    /**
     * Percentagem do stock relativamente ao máximo.
     */
    public function getStockPercentageAttribute(): ?float
    {
        if (
            $this->maximum_quantity === null
            || $this->maximum_quantity <= 0
        ) {
            return null;
        }

        return round(
            ($this->quantity / $this->maximum_quantity) * 100,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Nome da categoria.
     */
    public function getCategoryNameAttribute(): ?string
    {
        return $this->category?->name;
    }

    /**
     * Nome do fornecedor.
     */
    public function getSupplierNameAttribute(): ?string
    {
        return $this->supplier?->name;
    }

    /**
     * Estado do stock.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'Sem Stock';
        }

        if ($this->isLowStock()) {
            return 'Stock Baixo';
        }

        if ($this->isOverStock()) {
            return 'Stock Excessivo';
        }

        return 'Disponível';
    }

    /**
     * Código textual do estado do stock.
     */
    public function getStockStatusCodeAttribute(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }

        if ($this->isLowStock()) {
            return 'low_stock';
        }

        if ($this->isOverStock()) {
            return 'over_stock';
        }

        return 'available';
    }

    /**
     * Valor estimado do stock actual.
     */
    public function getStockValueAttribute(): float
    {
        return round(
            (float) $this->quantity
            * (float) $this->estimated_unit_cost,
            2
        );
    }

    /**
     * Nome para apresentação.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->sku) {
            return "{$this->sku} - {$this->name}";
        }

        return $this->name;
    }

    /**
     * Localização apresentada.
     */
    public function getLocationLabelAttribute(): string
    {
        return $this->location
            ?: 'Não definida';
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTODOS DE STOCK
    |--------------------------------------------------------------------------
    |
    | Estes métodos NÃO alteram directamente o stock.
    | Servem apenas para validação/cálculo.
    |
    */

    /**
     * Verifica se uma saída é possível.
     */
    public function canRemoveStock(
        int $quantity
    ): bool {
        return $quantity > 0
            && $this->quantity >= $quantity;
    }

    /**
     * Calcula o saldo após entrada.
     */
    public function balanceAfterEntry(
        int $quantity
    ): int {
        return $this->quantity + max(
            0,
            $quantity
        );
    }

    /**
     * Calcula o saldo após saída.
     */
    public function balanceAfterExit(
        int $quantity
    ): int {
        return $this->quantity - max(
            0,
            $quantity
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INVENTÁRIO
    |--------------------------------------------------------------------------
    */

    /**
     * Regista os dados do último inventário.
     *
     * O ajuste efectivo do stock deve continuar a ser feito através
     * de StockMovementController.
     */
    public function markInventory(
        ?int $userId = null
    ): void {
        $this->last_inventory_at = now();

        $this->last_inventory_by =
            $userId;

        $this->save();
    }
}
