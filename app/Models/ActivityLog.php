<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    use HasFactory;

    /**
     * Nome da tabela.
     */
    protected $table = 'activity_logs';

    /**
     * Campos preenchíveis.
     */
    protected $fillable = [

        'user_id',

        'action',

        'entity',

        'entity_id',

        'ip_address',

        'user_agent',

        'url',

        'http_method',

        'metadata',

    ];

    /**
     * Conversões.
     */
    protected $casts = [

        'metadata' => 'array',

    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Utilizador.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Logs por utilizador.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Logs por entidade.
     */
    public function scopeByEntity($query, string $entity)
    {
        return $query->where('entity', $entity);
    }

    /**
     * Logs por acção.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Mais recentes.
     */
    public function scopeLatestFirst($query)
    {
        return $query->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Possui utilizador?
     */
    public function hasUser(): bool
    {
        return !is_null($this->user_id);
    }

    /**
     * Possui metadados?
     */
    public function hasMetadata(): bool
    {
        return !empty($this->metadata);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Nome do utilizador.
     */
    public function getUserNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    /**
     * Nome simples da entidade.
     */
    public function getEntityNameAttribute(): string
    {
        return class_basename($this->entity);
    }

    /**
     * Descrição resumida.
     */
    public function getDescriptionAttribute(): string
    {
        return sprintf(
            '%s - %s',
            $this->user_name ?? 'Sistema',
            $this->action
        );
    }

    /**
     * Endereço IP.
     */
    public function getIpAttribute(): ?string
    {
        return $this->ip_address;
    }

}
