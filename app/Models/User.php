<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * Guard do Spatie.
     */
    protected string $guard_name = 'web';

    /**
     * Tabela.
     */
    protected $table = 'users';

    /**
     * Atributos preenchíveis.
     */
    protected $fillable = [

        'name',

        'email',

        'department_id',

        'active',

        'password',

    ];

    /**
     * Ocultos.
     */
    protected $hidden = [

        'password',

        'remember_token',

    ];

    /**
     * Conversões.
     */
    protected $casts = [

        'email_verified_at' => 'datetime',

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
     * Departamento.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Pedidos criados.
     */
    public function requests(): HasMany
    {
        return $this->hasMany(
            InternalRequest::class,
            'requested_by'
        );
    }

    /**
     * Pedidos para aprovação.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(
            InternalRequest::class,
            'assigned_approver_id'
        );
    }

    /**
     * Decisões tomadas.
     */
    public function approvalHistory(): HasMany
    {
        return $this->hasMany(
            Approval::class,
            'approved_by'
        );
    }

    /**
     * Documentos.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Auditoria.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Distribuições efectuadas.
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(
            Distribution::class,
            'distributed_by'
        );
    }

    /**
     * Movimentos de stock.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(
            StockMovement::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    public function isDirectorGeral(): bool
    {
        return $this->hasRole('director_geral');
    }

    public function isDirector(): bool
    {
        return $this->hasRole('director');
    }

    public function isFuncionario(): bool
    {
        return $this->hasRole('funcionario');
    }

    public function isFuncionarioArmazem(): bool
    {
        return $this->hasRole('funcionario_armazem');
    }

    public function isAdministrador(): bool
    {
        return $this->hasAnyRole([
            'director_geral',
            'director'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getRoleNameAttribute(): ?string
    {
        return $this->getRoleNames()->first();
    }

    public function getDepartmentNameAttribute(): ?string
    {
        return $this->department?->name;
    }

    public function getFullNameAttribute(): string
    {
        return $this->name;
    }
}
