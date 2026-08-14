<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory;

    /**
     * Nome da tabela.
     */
    protected $table = 'suppliers';

    /**
     * Campos preenchíveis.
     */
    protected $fillable = [

        'name',

        'nif',

        'phone',

        'email',

        'address',

        'active',

    ];

    /**
     * Conversões.
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
     * Utensílios fornecidos.
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
     * Apenas fornecedores activos.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Apenas fornecedores inactivos.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /**
     * Pesquisa.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {

            $q->where('name', 'like', "%{$search}%")
              ->orWhere('nif', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");

        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Está activo?
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * Possui utensílios associados?
     */
    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    /**
     * Total de utensílios.
     */
    public function totalProducts(): int
    {
        return $this->products()->count();
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
        return $this->name;
    }

    /**
     * Contacto principal.
     */
    public function getPrimaryContactAttribute(): ?string
    {
        return $this->phone ?: $this->email;
    }

    /**
     * Identificação completa.
     */
    public function getFullIdentificationAttribute(): string
    {
        if (!$this->nif) {
            return $this->name;
        }

        return "{$this->name} ({$this->nif})";
    }

    /**
     * Quantidade de utensílios.
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }

    /**
     * Endereço resumido.
     */
    public function getShortAddressAttribute(): ?string
    {
        return $this->address ?: 'Sem endereço';
    }

    /**
     * Resumo.
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s | %s | %s',
            $this->name,
            $this->phone ?? '-',
            $this->email ?? '-'
        );
    }
}
