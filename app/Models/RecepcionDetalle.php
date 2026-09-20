<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecepcionDetalle extends Model
{
    protected $table = 'recepcion_detalles';

    protected $fillable = [
        'recepcion_id',
        'producto_id',
        'almacen_id',
        'cantidad',
        'bultos',
        'unidades_por_bulto',
        'costo_bulto_usd',
        'costo_bulto_bs',
        'costo_anterior_usd',
        'costo_anterior_bs',
        'costo_unitario_usd',
        'costo_unitario_bs',
        'descuento_porcentaje',
        'descuento_usd',
        'descuento_bs',
        'aplica_iva',
        'iva_porcentaje',
        'iva_monto_usd',
        'iva_monto_bs',
        'precio_detal_anterior_usd',
        'precio_detal_anterior_bs',
        'margen_detal_porcentaje',
        'precio_detal_usd',
        'precio_detal_bs',
        'precio_mayorista_anterior_usd',
        'precio_mayorista_anterior_bs',
        'margen_mayorista_porcentaje',
        'precio_mayorista_usd',
        'precio_mayorista_bs',
        'subtotal_usd',
        'subtotal_bs',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:3',
            'bultos' => 'decimal:3',
            'unidades_por_bulto' => 'decimal:3',
            'costo_bulto_usd' => 'decimal:4',
            'costo_bulto_bs' => 'decimal:4',
            'costo_anterior_usd' => 'decimal:4',
            'costo_anterior_bs' => 'decimal:4',
            'costo_unitario_usd' => 'decimal:4',
            'costo_unitario_bs' => 'decimal:4',
            'descuento_porcentaje' => 'decimal:2',
            'descuento_usd' => 'decimal:4',
            'descuento_bs' => 'decimal:4',
            'aplica_iva' => 'boolean',
            'iva_porcentaje' => 'decimal:2',
            'iva_monto_usd' => 'decimal:4',
            'iva_monto_bs' => 'decimal:4',
            'precio_detal_anterior_usd' => 'decimal:4',
            'precio_detal_anterior_bs' => 'decimal:4',
            'margen_detal_porcentaje' => 'decimal:2',
            'precio_detal_usd' => 'decimal:4',
            'precio_detal_bs' => 'decimal:4',
            'precio_mayorista_anterior_usd' => 'decimal:4',
            'precio_mayorista_anterior_bs' => 'decimal:4',
            'margen_mayorista_porcentaje' => 'decimal:2',
            'precio_mayorista_usd' => 'decimal:4',
            'precio_mayorista_bs' => 'decimal:4',
            'subtotal_usd' => 'decimal:2',
            'subtotal_bs' => 'decimal:2',
        ];
    }

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(Recepcion::class, 'recepcion_id');
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
