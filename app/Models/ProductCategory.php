<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    use HasFactory;

    /**
     * Nome da tabela.
     */
    protected $table = 'product_categories';

    /**
     * Atributos preenchíveis.
     */
    protected $fillable = [

        'name',

        'code',

        'description',

        'active',

    ];

    /**
     * Conversão de atributos.
     */
    protected $casts = [

        'active' => 'boolean',

    ];

    /**
     * Valores por defeito.
     */
    protected $attributes = [

        'active' => true,

    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Utensílios da categoria.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Apenas categorias activas.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Nome completo.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Total de utensílios.
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }
}
