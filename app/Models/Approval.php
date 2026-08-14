<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasFactory;

    /**
     * Nome da tabela.
     */
    protected $table = 'approvals';

    /**
     * Campos preenchíveis.
     */
    protected $fillable = [

        'internal_request_id',

        'approved_by',

        'approval_level',

        'decision',

        'comment',

        'decided_at',

    ];

    /**
     * Conversões.
     */
    protected $casts = [

        'decided_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    /**
     * Pedido.
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(
            InternalRequest::class,
            'internal_request_id'
        );
    }

    /**
     * Aprovador.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Aprovações.
     */
    public function scopeApproved($query)
    {
        return $query->where(
            'decision',
            'aprovado'
        );
    }

    /**
     * Rejeições.
     */
    public function scopeRejected($query)
    {
        return $query->where(
            'decision',
            'rejeitado'
        );
    }

    /**
     * Mais recentes.
     */
    public function scopeLatestFirst($query)
    {
        return $query->latest('decided_at');
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Pedido aprovado?
     */


    /**
     * Pedido rejeitado?
     */
    public function isRejected(): bool
    {
        return $this->decision === 'rejeitado';
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Nome do aprovador.
     */
    public function getApproverNameAttribute(): ?string
    {
        return $this->approver?->name;
    }

    /**
     * Número do pedido.
     */
    public function getRequestNumberAttribute(): ?string
    {
        return $this->request?->request_number;
    }

    /**
     * Decisão formatada.
     */


    /**
     * Resumo da aprovação.
     */
    public function getSummaryAttribute(): string
    {
        return sprintf(
            '%s - %s',
            $this->request_number,
            ucfirst($this->decision)
        );
    }
    public function isPending(): bool
    {
        return $this->decision === 'pendente';
    }

    public function isApproved(): bool
    {
        return $this->decision === 'aprovado';
    }



    public function isForwarded(): bool
    {
        return $this->decision === 'reencaminhado';
    }

    public function getDecisionLabelAttribute(): string
    {
        return match ($this->decision) {
            'pendente' => 'Pendente',
            'aprovado' => 'Aprovado',
            'rejeitado' => 'Rejeitado',
            'reencaminhado' => 'Reencaminhado',
            'cancelado' => 'Cancelado',
            default => ucfirst($this->decision),
        };
    }
}
