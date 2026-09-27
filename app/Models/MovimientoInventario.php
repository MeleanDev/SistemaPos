<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'tipo',
        'almacen_origen_id',
        'almacen_destino_id',
        'user_id',
        'motivo',
        'observaciones',
        'fecha',
        'total_items',
        'total_unidades',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'total_items' => 'integer',
            'total_unidades' => 'decimal:3',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kardex(): HasMany
    {
        return $this->hasMany(Kardex::class, 'documento_id')->where('documento_tipo', 'movimiento_inventario');
    }
}
