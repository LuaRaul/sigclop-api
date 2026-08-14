<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    /**
     * Nome da tabela.
     */
    protected $table = 'departments';

    /**
     * Campos preenchíveis.
     */
    protected $fillable = [

        'name',

        'code',

        'description',

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
     * Utilizadores.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Pedidos Internos.
     */
    public function internalRequests(): HasMany
    {
        return $this->hasMany(InternalRequest::class);
    }

    /**
     * Documentos.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Distribuições.
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Apenas departamentos activos.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Apenas departamentos inactivos.
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Possui utilizadores?
     */
    public function hasUsers(): bool
    {
        return $this->users()->exists();
    }

    /**
     * Possui pedidos?
     */
    public function hasRequests(): bool
    {
        return $this->internalRequests()->exists();
    }

    /**
     * Está activo?
     */
    public function isActive(): bool
    {
        return $this->active;
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
     * Total de utilizadores.
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Total de pedidos.
     */
    public function getRequestsCountAttribute(): int
    {
        return $this->internalRequests()->count();
    }

    /**
     * Total de documentos.
     */
    public function getDocumentsCountAttribute(): int
    {
        return $this->documents()->count();
    }

    /**
     * Total de distribuições.
     */
    public function getDistributionsCountAttribute(): int
    {
        return $this->distributions()->count();
    }
}
