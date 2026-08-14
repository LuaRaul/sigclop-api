<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    use HasFactory;

    /**
     * Nome da tabela.
     */
    protected $table = 'document_categories';

    /**
     * Campos preenchíveis.
     */
    protected $fillable = [

        'name',

        'code',

        'description',

        'requires_approval',

        'allow_attachment',

        'allow_versioning',

        'retention_period',

        'confidential_level',

        'active',

    ];

    protected $casts = [

        'active' => 'boolean',

        'requires_approval' => 'boolean',

        'allow_attachment' => 'boolean',

        'allow_versioning' => 'boolean',

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
     * Documentos da categoria.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(
            Document::class,
            'document_category_id'
        );
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

    /**
     * Apenas categorias inactivas.
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
     * Possui documentos?
     */
    public function hasDocuments(): bool
    {
        return $this->documents()->exists();
    }

    /**
     * Está activa?
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
     * Total de documentos.
     */
    public function getDocumentsCountAttribute(): int
    {
        return $this->documents()->count();
    }

    /**
     * Nome formatado.
     */
    public function getFormattedNameAttribute(): string
    {
        return strtoupper($this->code) . ' | ' . $this->name;
    }

    /**
     * Resumo.
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s (%d documentos)',
            $this->name,
            $this->documents()->count()
        );
    }
}
