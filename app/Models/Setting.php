<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * Nome da tabela.
     */
    protected $table = 'settings';

    /**
     * Campos preenchíveis.
     */
    protected $fillable = [

        'key',

        'value',

        'group',

        'type',

        'label',

        'description',

        'is_public',

        'editable',

        'updated_by',

    ];

    protected $casts = [

        'is_public' => 'boolean',

        'editable' => 'boolean',

    ];

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Filtrar por grupo.
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Filtrar por tipo.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Obter valor por chave.
     */
    public static function value(string $key, $default = null)
    {
        return static::where('key', $key)
            ->value('value') ?? $default;
    }

    /**
     * Definir valor.
     */
    public static function set(string $key, $value): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Existe configuração?
     */
    public static function has(string $key): bool
    {
        return static::where('key', $key)->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Valor convertido.
     */
    public function getParsedValueAttribute()
    {
        return match ($this->type) {

            'boolean' => filter_var(
                $this->value,
                FILTER_VALIDATE_BOOLEAN
            ),

            'number' => is_numeric($this->value)
                ? (str_contains((string) $this->value, '.')
                    ? (float) $this->value
                    : (int) $this->value)
                : 0,

            'date' => $this->value
                ? \Carbon\Carbon::parse($this->value)
                : null,

            'json' => json_decode(
                $this->value,
                true
            ),

            default => $this->value,
        };
    }

    /**
     * Nome amigável.
     */
    public function getDisplayNameAttribute(): string
    {
        return ucwords(
            str_replace('_', ' ', $this->key)
        );
    }
    public function updatedBy()
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }
}
