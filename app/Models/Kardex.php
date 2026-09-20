<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kardex extends Model
{
    protected $table = 'kardex';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'producto_id',
        'user_id',
        'tipo_movimiento',
        'documento_tipo',
        'documento_id',
        'cantidad',
        'costo_unitario_usd',
        'costo_unitario_bs',
        'stock_anterior',
        'stock_nuevo',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'costo_unitario_usd' => 'decimal:4',
            'costo_unitario_bs' => 'decimal:4',
            'stock_anterior' => 'decimal:3',
            'stock_nuevo' => 'decimal:3',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
