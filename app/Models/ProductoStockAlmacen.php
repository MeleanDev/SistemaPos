<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoStockAlmacen extends Model
{
    protected $table = 'producto_stock_almacenes';

    protected $fillable = [
        'producto_id',
        'almacen_id',
        'cantidad_actual',
        'cantidad_reservada',
        'ubicacion_pasillo',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_actual' => 'decimal:3',
            'cantidad_reservada' => 'decimal:3',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }
}
