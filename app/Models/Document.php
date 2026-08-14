<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    use HasFactory;

    /**
     * Nome da tabela.
     */
    protected $table = 'documents';

    /**
     * Campos preenchíveis.
     */
    protected $fillable = [

        'document_category_id',

        'department_id',

        'user_id',

        'title',

        'reference_number',

        'document_date',

        'file_name',

        'file_path',

        'mime_type',

        'file_size',

        'version',

        'status',

        'approved_by',

        'approved_at',

        'expires_at',

        'summary',

    ];

    /**
     * Conversões.
     */
    protected $casts = [

        'document_date' => 'date',

        'approved_at' => 'datetime',

        'expires_at' => 'date',

    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Categoria.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            DocumentCategory::class,
            'document_category_id'
        );
    }

    /**
     * Departamento.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class
        );
    }

    /**
     * Utilizador responsável.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Documentos activos.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'activo');
    }

    /**
     * Documentos arquivados.
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'arquivado');
    }

    /**
     * Pesquisa.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {

            $q->where('title', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%")
                ->orWhere('summary', 'like', "%{$search}%");
        });
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Está arquivado?
     */
    public function isArchived(): bool
    {
        return $this->status === 'arquivado';
    }

    /**
     * Está activo?
     */
    public function isActive(): bool
    {
        return $this->status === 'activo';
    }

    /**
     * Possui ficheiro?
     */
    public function hasFile(): bool
    {
        return !empty($this->file_path);
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
     * Nome do departamento.
     */
    public function getDepartmentNameAttribute(): ?string
    {
        return $this->department?->name;
    }

    /**
     * Nome do responsável.
     */
    public function getUserNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    /**
     * Estado formatado.
     */
    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Nome para apresentação.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->reference_number} - {$this->title}";
    }

    /**
     * Nome do ficheiro.
     */
    public function getFileNameAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return basename($this->file_path);
    }
    public function approvedBy()
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }
}
