<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoProveedor extends Model
{
    protected $table = 'producto_proveedores';

    protected $fillable = [
        'producto_id',
        'proveedor_id',
        'codigo_proveedor',
        'ultimo_costo_usd',
        'ultimo_costo_bs',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_costo_usd' => 'decimal:4',
            'ultimo_costo_bs' => 'decimal:4',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
