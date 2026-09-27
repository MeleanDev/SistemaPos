<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendedor extends Model
{
    use HasFactory;

    protected $table = 'vendedores';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'tipo_documento',
        'documento',
        'nombre',
        'telefono',
        'correo',
        'comision_porcentaje',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'comision_porcentaje' => 'decimal:2',
            'estado' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'vendedor_id');
    }

    public function getDocumentoCompletoAttribute(): string
    {
        return "{$this->tipo_documento}-{$this->documento}";
    }
}
