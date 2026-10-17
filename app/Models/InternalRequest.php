<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalRequest extends Model
{
    use HasFactory;

    protected $table = 'internal_requests';

    protected $fillable = [
        'request_number',
        'request_type',
        'department_id',
        'requested_by',
        'assigned_approver_id',
        'priority',
        'status',
        'justification',
        'needed_at',
        'submitted_at',
        'approved_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'needed_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = [
        'items_count',
        'total_quantity',
        'approved_quantity',
        'pending_approval_quantity',
        'requester_name',
        'approver_name',
        'department_name',
        'priority_label',
        'status_label',
        'request_type_label',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONAMENTOS
    |--------------------------------------------------------------------------
    */

    public function department(): BelongsTo
    {
        return $this->belongsTo(
            Department::class,
            'department_id'
        );
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'requested_by'
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'assigned_approver_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(
            RequestItem::class,
            'internal_request_id'
        );
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(
            Approval::class,
            'internal_request_id'
        );
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(
            Distribution::class,
            'internal_request_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where(
            'status',
            'pendente'
        );
    }

    public function scopeInAnalysis($query)
    {
        return $query->where(
            'status',
            'em_analise'
        );
    }

    public function scopeApproved($query)
    {
        return $query->where(
            'status',
            'aprovado'
        );
    }

    public function scopeRejected($query)
    {
        return $query->where(
            'status',
            'rejeitado'
        );
    }

    public function scopeInDistribution($query)
    {
        return $query->where(
            'status',
            'em_distribuicao'
        );
    }

    public function scopeCompleted($query)
    {
        return $query->where(
            'status',
            'concluido'
        );
    }

    public function scopeCancelled($query)
    {
        return $query->where(
            'status',
            'cancelado'
        );
    }

    public function scopeByDepartment(
        $query,
        int $departmentId
    ) {
        return $query->where(
            'department_id',
            $departmentId
        );
    }

    public function scopeByRequester(
        $query,
        int $userId
    ) {
        return $query->where(
            'requested_by',
            $userId
        );
    }

    public function scopeAssignedTo(
        $query,
        int $userId
    ) {
        return $query->where(
            'assigned_approver_id',
            $userId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADO
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === 'pendente';
    }

    public function isInAnalysis(): bool
    {
        return $this->status === 'em_analise';
    }

    public function isApproved(): bool
    {
        return $this->status === 'aprovado';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejeitado';
    }

    public function isInDistribution(): bool
    {
        return $this->status === 'em_distribuicao';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'concluido';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelado';
    }

    public function isFinalized(): bool
    {
        return in_array(
            $this->status,
            [
                'rejeitado',
                'concluido',
                'cancelado',
            ],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TRANSIÇÕES
    |--------------------------------------------------------------------------
    */

    public function canBeEdited(): bool
    {
        return in_array(
            $this->status,
            [
                'pendente',
                'em_analise',
            ],
            true
        );
    }

    public function canBeApproved(): bool
    {
        return in_array(
            $this->status,
            [
                'pendente',
                'em_analise',
            ],
            true
        );
    }

    public function canBeRejected(): bool
    {
        return in_array(
            $this->status,
            [
                'pendente',
                'em_analise',
            ],
            true
        );
    }

    public function canBeDistributed(): bool
    {
        return $this->status === 'aprovado';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'em_distribuicao';
    }

    public function canBeCancelled(): bool
    {
        return in_array(
            $this->status,
            [
                'pendente',
                'em_analise',
                'aprovado',
            ],
            true
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QUANTIDADES
    |--------------------------------------------------------------------------
    */

    public function getItemsCountAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return $this->items->count();
        }

        return $this->items()->count();
    }

    public function getTotalQuantityAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum(
                'quantity_requested'
            );
        }

        return (int) $this->items()->sum(
            'quantity_requested'
        );
    }

    public function getApprovedQuantityAttribute(): int
    {
        if ($this->relationLoaded('items')) {
            return (int) $this->items->sum(
                'quantity_approved'
            );
        }

        return (int) $this->items()->sum(
            'quantity_approved'
        );
    }

    public function getPendingApprovalQuantityAttribute(): int
    {
        return max(
            0,
            $this->total_quantity
            - $this->approved_quantity
        );
    }

    /*
    |--------------------------------------------------------------------------
    | APROVAÇÃO
    |--------------------------------------------------------------------------
    */

    public function isFullyApproved(): bool
    {
        if ($this->items_count === 0) {
            return false;
        }

        return $this->approved_quantity >=
            $this->total_quantity;
    }

    public function isPartiallyApproved(): bool
    {
        return $this->approved_quantity > 0
            && $this->approved_quantity <
                $this->total_quantity;
    }

    public function latestApproval(): ?Approval
    {
        if ($this->relationLoaded('approvals')) {

            return $this->approvals
                ->sortByDesc('decided_at')
                ->first();
        }

        return $this->approvals()
            ->latest('decided_at')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getRequesterNameAttribute(): ?string
    {
        return $this->requester?->name;
    }

    public function getApproverNameAttribute(): ?string
    {
        return $this->approver?->name;
    }

    public function getDepartmentNameAttribute(): ?string
    {
        return $this->department?->name;
    }

    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {

            'baixa' =>
                'Baixa',

            'normal' =>
                'Normal',

            'alta' =>
                'Alta',

            'urgente' =>
                'Urgente',

            default =>
                ucfirst(
                    (string) $this->priority
                ),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {

            'pendente' =>
                'Pendente',

            'em_analise' =>
                'Em análise',

            'aprovado' =>
                'Aprovado',

            'rejeitado' =>
                'Rejeitado',

            'em_distribuicao' =>
                'Em distribuição',

            'concluido' =>
                'Concluído',

            'cancelado' =>
                'Cancelado',

            default =>
                ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        (string) $this->status
                    )
                ),
        };
    }
    public function getRequestTypeLabelAttribute(): string
    {
        return match ($this->request_type) {
            'vestuario' => 'Vestuário',
            'higiene' => 'Artigos de higiene',
            'meios_oficina' => 'Meios de oficina',
            default => ucfirst(str_replace('_', ' ', (string) $this->request_type)),
        };
    }

}
